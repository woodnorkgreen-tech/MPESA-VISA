<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventState;
use App\Models\Player;
use App\Models\Prediction;
use App\Models\Question;
use App\Models\MatchConfig;
use App\Services\ScoringService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlayerApiController extends Controller
{
    // ── Registration ──────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nickname'      => 'required|string|max:50',
            'phone'         => ['required', 'string', 'max:20', 'regex:/^(?:\+?254|0)?[17](?:[\s-]?\d){8}$/'],
            'email'         => 'nullable|email|max:100',
            'consent'       => 'required|accepted',
            'has_visa_card' => 'boolean',
        ]);

        $phone = Player::normalisePhone($data['phone']);

        try {
            $player = Player::firstOrCreate(
                ['phone' => $phone],
                [
                    'nickname'      => $data['nickname'],
                    'email'         => $data['email'] ?? null,
                    'consent'       => true,
                    'has_visa_card' => $data['has_visa_card'] ?? false,
                ]
            );
        } catch (UniqueConstraintViolationException) {
            // Two simultaneous requests for the same phone — safe to return the existing record
            $player = Player::where('phone', $phone)->firstOrFail();
        }

        return response()->json([
            'player_id' => $player->id,
            'nickname'  => $player->nickname,
            'session_token' => $player->issueSessionToken(),
            'message'   => 'You\'re in! 🎉',
        ], 201);
    }

    // ── Answer submission ─────────────────────────────────────────────────────

    public function submitAnswer(Request $request, ScoringService $scoring): JsonResponse
    {
        $data = $request->validate([
            'player_id'       => 'required|exists:players,id',
            'question_id'     => 'required|exists:questions,id',
            'selected_option' => 'required|string|max:255',
            'response_time_ms'=> 'required|integer|min:0',
        ]);

        $state    = EventState::current();
        $question = Question::findOrFail($data['question_id']);

        $request->validate([
            'selected_option' => ['required', 'string', Rule::in($question->options)],
        ]);

        if ($state->phase !== 'trivia_live' || $question->status !== 'live' || $question->secondsRemaining() <= 0) {
            return response()->json(['message' => 'Question is no longer accepting answers.'], 422);
        }

        $player = Player::findOrFail($data['player_id']);
        $this->assertPlayerSession($request, $player);

        $existing = $player->answers()->where('question_id', $question->id)->first();
        // Never trust a phone's clock for scoring. The server activation time is authoritative.
        $serverResponseMs = $question->activated_at
            ? min(
                $question->duration_seconds * 1000,
                max(0, (int) $question->activated_at->diffInMilliseconds(now()))
            )
            : $question->duration_seconds * 1000;
        $points = $scoring->scoreAnswer($player, $question, $data['selected_option'], $serverResponseMs);

        return response()->json([
            'answer_updated' => (bool) $existing,
            'selected_option'=> $data['selected_option'],
            'is_correct'    => $data['selected_option'] === $question->correct_answer,
            'points_awarded'=> $points,
            'total_score'   => $player->fresh()->trivia_score,
            'message'       => $existing ? 'Answer updated. You can change it again while the timer is running.' : 'Answer saved. You can change it while the timer is running.',
        ]);
    }

    /** Return the authoritative saved result used by the player's reveal UI. */
    public function answerResult(Request $request): JsonResponse
    {
        $data = $request->validate([
            'player_id'   => 'required|exists:players,id',
            'question_id' => 'required|exists:questions,id',
        ]);

        $player = Player::findOrFail($data['player_id']);
        $this->assertPlayerSession($request, $player);
        $answer = $player->answers()
            ->where('question_id', $data['question_id'])
            ->first();

        if (!$answer) {
            return response()->json([
                'answered'    => false,
                'total_score' => $player->trivia_score,
            ]);
        }

        return response()->json([
            'answered'       => true,
            'is_correct'     => $answer->is_correct,
            'points_awarded' => $answer->points_awarded,
            'total_score'    => $player->trivia_score,
        ]);
    }

    // ── Predictions ───────────────────────────────────────────────────────────

    public function submitPrediction(Request $request): JsonResponse
    {
        $matchConfig = MatchConfig::current();
        $players = $matchConfig->players();
        $data = $request->validate([
            'player_id'    => 'required|exists:players,id',
            'score_home'   => 'required|integer|min:0|max:20',
            'score_away'   => 'required|integer|min:0|max:20',
            'first_scorer' => 'required|string|max:100',
            'potm'         => 'required|string|max:100',
        ]);

        $request->validate([
            'first_scorer' => ['required', Rule::in(array_merge($players, ['No goal / N/A']))],
            'potm' => ['required', Rule::in(array_merge($players, ['TBD']))],
        ]);

        $state = EventState::current();
        if (!in_array($state->phase, ['predictions_open', 'lobby'])) {
            return response()->json(['message' => 'Predictions are closed.'], 422);
        }

        $player = Player::findOrFail($data['player_id']);
        $this->assertPlayerSession($request, $player);

        $prediction = Prediction::updateOrCreate(
            ['player_id' => $data['player_id']],
            [
                'score_home'   => $data['score_home'],
                'score_away'   => $data['score_away'],
                'first_scorer' => $data['first_scorer'],
                'potm'         => $data['potm'],
                'resolved'     => false,
            ]
        );

        return response()->json(['message' => 'Predictions locked in! ⚽', 'prediction_id' => $prediction->id]);
    }

    public function currentPrediction(Request $request): JsonResponse
    {
        $data = $request->validate([
            'player_id' => 'required|exists:players,id',
        ]);
        $player = Player::findOrFail($data['player_id']);
        $this->assertPlayerSession($request, $player);
        $prediction = $player->prediction;

        return response()->json([
            'prediction' => $prediction ? [
                'score_home' => $prediction->score_home,
                'score_away' => $prediction->score_away,
                'first_scorer' => $prediction->first_scorer,
                'potm' => $prediction->potm,
                'updated_at' => $prediction->updated_at?->toIso8601String(),
            ] : null,
        ]);
    }

    // ── Phone lookup (re-identification after session loss) ───────────────────

    public function lookup(Request $request): JsonResponse
    {
        $data  = $request->validate([
            'phone' => ['required', 'string', 'max:20', 'regex:/^(?:\+?254|0)?[17](?:[\s-]?\d){8}$/'],
        ]);
        $phone = Player::normalisePhone($data['phone']);
        $player = Player::where('phone', $phone)->first();

        if (!$player) {
            return response()->json(['message' => 'No player found with that number.'], 404);
        }

        return response()->json([
            'player_id' => $player->id,
            'nickname'  => $player->nickname,
            'session_token' => $player->issueSessionToken(),
        ]);
    }

    // ── Leaderboard ───────────────────────────────────────────────────────────

    public function leaderboard(ScoringService $scoring): JsonResponse
    {
        return response()->json([
            'trivia'     => $scoring->triviaLeaderboard(10),
            'prediction' => $scoring->predictionLeaderboard(10),
        ]);
    }

    private function assertPlayerSession(Request $request, Player $player): void
    {
        $token = (string) $request->header('X-Player-Token', '');
        $valid = $token !== ''
            && $player->session_token_hash
            && hash_equals($player->session_token_hash, hash('sha256', $token));

        abort_unless($valid, 401, 'Your player session has expired. Please sign in again.');
    }
}
