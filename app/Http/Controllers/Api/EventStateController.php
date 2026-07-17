<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventState;
use App\Models\EventAudit;
use App\Models\Player;
use App\Models\Prediction;
use App\Models\Question;
use App\Models\MatchConfig;
use App\Services\ScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EventStateController extends Controller
{
    public function show(ScoringService $scoring): JsonResponse
    {
        $payload = app()->environment('testing')
            ? $this->buildPayload($scoring)
            : Cache::remember('public-event-state-v3', now()->addSecond(), fn () => $this->buildPayload($scoring));

        return response()->json($payload)->header('Cache-Control', 'no-store');
    }

    private function buildPayload(ScoringService $scoring): array
    {
        $state    = $this->closeExpiredQuestion(EventState::current());
        $matchConfig = MatchConfig::current();
        $question = null;
        $round = ['current' => 0, 'total' => 0, 'completed' => 0];

        $roundQuestions = Question::where('status', '!=', 'skipped')
            ->orderBy('order_index')
            ->orderBy('id')
            ->get(['id', 'status']);
        $round['total'] = $roundQuestions->count();
        $round['completed'] = $roundQuestions->where('status', 'closed')->count();

        if ($state->current_question_id) {
            $q = Question::find($state->current_question_id);
            if ($q) {
                $round['current'] = $roundQuestions->search(fn ($candidate) => $candidate->id === $q->id) + 1;
                $canRevealAnswers = in_array($state->phase, ['trivia_reveal', 'trivia_complete']);
                $question = [
                    'id'              => $q->id,
                    'order_index'     => $q->order_index,
                    'type'            => $q->type,
                    'text'            => $q->text,
                    'options'         => $q->options,
                    'duration_seconds'=> $q->duration_seconds,
                    'is_double_points'=> $q->is_double_points,
                    'seconds_remaining' => $q->status === 'live' ? $q->secondsRemaining() : 0,
                    'status'          => $q->status,
                    'answer_count'    => $q->answers()->count(),
                    'answer_distribution' => $canRevealAnswers
                        ? $q->answers()->selectRaw('selected_option, COUNT(*) as total')
                            ->groupBy('selected_option')->pluck('total', 'selected_option')
                        : null,
                    // Never expose the answer while submissions are still open.
                    'correct_answer'  => $canRevealAnswers
                        ? $q->correct_answer : null,
                ];
            }
        }

        // Recent prediction submitters for lobby ticker (last 20, newest first)
        $recentPredictions = Prediction::with('player')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->pluck('player.nickname')
            ->filter()
            ->values()
            ->toArray();

        return [
            'phase'               => $state->phase,
            'server_time'         => now()->toIso8601String(),
            'state_version'       => $state->updated_at?->getTimestampMs(),
            'round'               => $round,
            'question'            => $question,
            'show_phone_on_screen'=> (bool) $state->show_phone_on_screen,
            // The main screen can scroll a deep field; do not cap it to a top ten.
            'leaderboard'         => in_array($state->phase, ['match_ended', 'prediction_reveal'])
                ? $scoring->predictionLeaderboard(100)
                : $scoring->triviaLeaderboard(100),
            'player_count'        => Player::count(),
            'prediction_count'    => Prediction::count(),
            'recent_predictions'  => $recentPredictions,
            'match'                => [
                'home_team' => $matchConfig->home_team,
                'away_team' => $matchConfig->away_team,
                'home_squad' => $matchConfig->home_squad ?? [],
                'away_squad' => $matchConfig->away_squad ?? [],
                'kickoff_at' => $matchConfig->kickoff_at?->toIso8601String(),
                'venue' => $matchConfig->venue,
            ],
        ];
    }

    /** Persist countdown expiry so the admin, player devices and screen agree. */
    private function closeExpiredQuestion(EventState $state): EventState
    {
        if ($state->phase !== 'trivia_live' || !$state->current_question_id) {
            return $state;
        }

        return DB::transaction(function () use ($state) {
            $lockedState = EventState::whereKey($state->id)->lockForUpdate()->firstOrFail();
            if ($lockedState->phase !== 'trivia_live' || !$lockedState->current_question_id) {
                return $lockedState->fresh();
            }

            $question = Question::whereKey($lockedState->current_question_id)->lockForUpdate()->first();
            if (!$question || $question->status !== 'live' || $question->secondsRemaining() > 0) {
                return $lockedState->fresh();
            }

            $question->update(['status' => 'closed']);
            $lockedState->update(['phase' => 'trivia_reveal']);
            EventAudit::record('question.auto_revealed', $question, [
                'correct_answer' => $question->correct_answer,
                'reason' => 'countdown_expired',
            ]);
            Cache::forget('public-event-state-v3');

            return $lockedState->fresh();
        });
    }

    public function togglePhone(Request $request): JsonResponse
    {
        $enabled = DB::transaction(function () {
            EventState::current();
            $state = EventState::whereKey(1)->lockForUpdate()->firstOrFail();
            $enabled = !$state->show_phone_on_screen;
            $state->update(['show_phone_on_screen' => $enabled]);
            EventAudit::record('display.phone_suffix_toggled', $state, ['enabled' => $enabled]);
            return $enabled;
        });

        Cache::forget('public-event-state-v3');

        return response()->json(['show_phone_on_screen' => $enabled]);
    }

}
