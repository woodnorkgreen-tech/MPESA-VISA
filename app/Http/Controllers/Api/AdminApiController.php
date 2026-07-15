<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventState;
use App\Models\EventAudit;
use App\Models\Answer;
use App\Models\MatchResult;
use App\Models\MatchConfig;
use App\Models\Player;
use App\Models\Prediction;
use App\Models\Question;
use App\Models\SportsPlayer;
use App\Models\SportsTeam;
use App\Services\ScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class AdminApiController extends Controller
{
    public function listPlayers(Request $request): JsonResponse
    {
        $data = $request->validate([
            'search' => 'nullable|string|max:80',
            'type' => 'nullable|in:all,real,simulated',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);
        $search = trim((string) ($data['search'] ?? ''));
        $type = $data['type'] ?? 'all';
        $perPage = $data['per_page'] ?? 20;

        $players = Player::query()
            ->withCount('answers')
            ->withExists('prediction')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('nickname', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($type === 'real', fn ($query) => $query->where('is_simulated', false))
            ->when($type === 'simulated', fn ($query) => $query->where('is_simulated', true))
            ->latest('id')
            ->paginate($perPage);

        return response()->json($players);
    }

    public function showPlayer(Player $player): JsonResponse
    {
        $player->load([
            'prediction',
            'answers' => fn ($query) => $query->with('question:id,order_index,text,correct_answer,category')->orderByDesc('server_received_at'),
        ]);

        return response()->json([
            'player' => $player,
            'summary' => [
                'total_score' => $player->trivia_score + $player->prediction_score,
                'answers_count' => $player->answers->count(),
                'correct_count' => $player->answers->where('is_correct', true)->count(),
            ],
        ]);
    }

    public function listTeams(): JsonResponse
    {
        return response()->json([
            'data' => SportsTeam::with(['players' => fn ($query) => $query->where('active', true)])
                ->orderBy('name')->get(),
        ]);
    }

    public function storeTeam(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:80|unique:sports_teams,name',
            'code' => 'nullable|string|size:3|unique:sports_teams,code',
            'country_code' => 'nullable|string|size:2',
        ]);
        $data['code'] = isset($data['code']) ? strtoupper($data['code']) : null;
        $data['country_code'] = isset($data['country_code']) ? strtoupper($data['country_code']) : null;
        $team = SportsTeam::create($data);
        EventAudit::record('team.created', $team, ['name' => $team->name]);

        return response()->json(['team' => $team->load('players')], 201);
    }

    public function updateTeam(Request $request, SportsTeam $team): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('sports_teams', 'name')->ignore($team)],
            'code' => ['nullable', 'string', 'size:3', Rule::unique('sports_teams', 'code')->ignore($team)],
            'country_code' => 'nullable|string|size:2',
        ]);
        $data['code'] = isset($data['code']) ? strtoupper($data['code']) : null;
        $data['country_code'] = isset($data['country_code']) ? strtoupper($data['country_code']) : null;
        $team->update($data);
        EventAudit::record('team.updated', $team, ['name' => $team->name]);

        return response()->json(['team' => $team->fresh()->load('players')]);
    }

    public function destroyTeam(SportsTeam $team): JsonResponse
    {
        EventAudit::record('team.deleted', $team, ['name' => $team->name]);
        $team->delete();

        return response()->json(['message' => 'Team deleted.']);
    }

    public function storeSportsPlayer(Request $request, SportsTeam $team): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('sports_players', 'name')->where('sports_team_id', $team->id)],
            'position' => 'nullable|in:GK,DF,MF,FW',
            'shirt_number' => 'nullable|integer|min:1|max:99',
        ]);
        $player = $team->players()->create($data);
        EventAudit::record('team.player_added', $player, ['team' => $team->name, 'name' => $player->name]);

        return response()->json(['player' => $player], 201);
    }

    public function updateSportsPlayer(Request $request, SportsPlayer $sportsPlayer): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('sports_players', 'name')->where('sports_team_id', $sportsPlayer->sports_team_id)->ignore($sportsPlayer)],
            'position' => 'nullable|in:GK,DF,MF,FW',
            'shirt_number' => 'nullable|integer|min:1|max:99',
            'active' => 'sometimes|boolean',
        ]);
        $sportsPlayer->update($data);

        return response()->json(['player' => $sportsPlayer->fresh()]);
    }

    public function destroySportsPlayer(SportsPlayer $sportsPlayer): JsonResponse
    {
        EventAudit::record('team.player_removed', $sportsPlayer, ['name' => $sportsPlayer->name]);
        $sportsPlayer->delete();

        return response()->json(['message' => 'Player removed.']);
    }

    public function simulatePlayers(Request $request, ScoringService $scoring): JsonResponse
    {
        $data = $request->validate([
            'count' => 'required|integer|min:1|max:200',
            'include_answers' => 'sometimes|boolean',
            'answer_rate' => 'sometimes|integer|min:0|max:100',
            'correct_rate' => 'sometimes|integer|min:0|max:100',
        ]);
        $match = MatchConfig::current();
        $squad = $match->players();
        $includeAnswers = (bool) ($data['include_answers'] ?? true);
        $answerRate = (int) ($data['answer_rate'] ?? 100);
        $correctRate = (int) ($data['correct_rate'] ?? 70);
        $questions = $includeAnswers ? Question::orderBy('order_index')->get() : collect();

        if (count($squad) < 2) {
            return response()->json(['message' => 'Configure both match squads before simulating users.'], 422);
        }

        $simulation = DB::transaction(function () use ($data, $squad, $questions, $answerRate, $correctRate, $scoring) {
            $created = 0;
            $answersCreated = 0;
            for ($i = 0; $i < $data['count']; $i++) {
                do {
                    $phone = '254799'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                } while (Player::where('phone', $phone)->exists());

                $player = Player::create([
                    'phone' => $phone,
                    'nickname' => 'Test Fan '.str_pad((string) (Player::where('is_simulated', true)->count() + 1), 3, '0', STR_PAD_LEFT),
                    'consent' => true,
                    'has_visa_card' => (bool) random_int(0, 1),
                    'is_simulated' => true,
                ]);

                Prediction::create([
                    'player_id' => $player->id,
                    'score_home' => random_int(0, 4),
                    'score_away' => random_int(0, 4),
                    'first_scorer' => $squad[array_rand($squad)],
                    'potm' => $squad[array_rand($squad)],
                ]);

                $score = 0;
                $streak = 0;
                $correctCount = 0;
                $doubleCorrect = 0;
                foreach ($questions as $question) {
                    if (random_int(1, 100) > $answerRate || empty($question->options)) {
                        continue;
                    }

                    $isCorrect = random_int(1, 100) <= $correctRate;
                    $wrongOptions = array_values(array_diff($question->options, [$question->correct_answer]));
                    $selected = $isCorrect || !$wrongOptions
                        ? $question->correct_answer
                        : $wrongOptions[array_rand($wrongOptions)];
                    $isCorrect = $selected === $question->correct_answer;
                    $responseTime = random_int(700, max(700, $question->duration_seconds * 1000));
                    $points = 0;

                    if ($isCorrect) {
                        $streak++;
                        $correctCount++;
                        $doubleCorrect += $question->is_double_points ? 1 : 0;
                        $secondsRemaining = max(0, $question->duration_seconds - (int) ceil($responseTime / 1000));
                        $points = $scoring->calculateTriviaPoints(
                            $question->is_double_points,
                            $secondsRemaining,
                            $question->duration_seconds,
                            $streak,
                        );
                        $score += $points;
                    } else {
                        $streak = 0;
                    }

                    Answer::create([
                        'player_id' => $player->id,
                        'question_id' => $question->id,
                        'selected_option' => $selected,
                        'is_correct' => $isCorrect,
                        'points_awarded' => $points,
                        'response_time_ms' => $responseTime,
                        'server_received_at' => now(),
                    ]);
                    $answersCreated++;
                }

                $player->update([
                    'trivia_score' => $score,
                    'trivia_streak' => $streak,
                    'trivia_correct_count' => $correctCount,
                    'trivia_double_correct' => $doubleCorrect,
                ]);
                $created++;
            }

            return ['players' => $created, 'answers' => $answersCreated];
        });

        EventAudit::record('testing.players_simulated', null, $simulation);

        return response()->json([
            'message' => "{$simulation['players']} users, {$simulation['players']} predictions and {$simulation['answers']} answers simulated.",
            'created' => $simulation['players'],
            'answers_created' => $simulation['answers'],
            'simulated_total' => Player::where('is_simulated', true)->count(),
        ], 201);
    }

    public function testingStatus(): JsonResponse
    {
        return response()->json([
            'players' => Player::count(),
            'real_players' => Player::where('is_simulated', false)->count(),
            'simulated_players' => Player::where('is_simulated', true)->count(),
            'predictions' => Prediction::count(),
            'answers' => Answer::count(),
            'questions' => Question::count(),
        ]);
    }

    public function clearSimulatedPlayers(): JsonResponse
    {
        $count = Player::where('is_simulated', true)->count();
        Player::where('is_simulated', true)->delete();
        EventAudit::record('testing.simulated_cleared', null, ['count' => $count]);

        return response()->json(['message' => "{$count} simulated users cleared.", 'deleted' => $count]);
    }

    public function resetEvent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'confirmed' => 'required|accepted',
            'confirmation' => 'nullable|string|max:30',
            'remove_players' => 'sometimes|boolean',
        ]);
        $removePlayers = (bool) ($data['remove_players'] ?? false);

        if ($removePlayers && strtoupper(trim((string) ($data['confirmation'] ?? ''))) !== 'RESET EVENT') {
            return response()->json([
                'message' => 'Type RESET EVENT to confirm removing every registered player.',
            ], 422);
        }

        $summary = DB::transaction(function () use ($removePlayers) {
            $summary = [
                'answers' => Answer::count(),
                'predictions' => \App\Models\Prediction::count(),
                'players' => $removePlayers ? Player::count() : 0,
            ];

            Answer::query()->delete();
            \App\Models\Prediction::query()->delete();
            MatchResult::query()->delete();
            Question::query()->update(['status' => 'draft', 'activated_at' => null]);

            if ($removePlayers) {
                Player::query()->delete();
            } else {
                Player::query()->update([
                    'trivia_score' => 0,
                    'trivia_streak' => 0,
                    'trivia_correct_count' => 0,
                    'trivia_double_correct' => 0,
                    'prediction_score' => 0,
                ]);
            }

            EventState::setCurrent([
                'phase' => 'lobby',
                'current_question_id' => null,
                'show_phone_on_screen' => false,
            ]);

            return $summary;
        });

        EventAudit::record('event.reset', null, array_merge($summary, ['removed_players' => $removePlayers]));

        return response()->json(['message' => 'Event reset completed.', 'summary' => $summary]);
    }

    public function showMatchConfig(): JsonResponse
    {
        $config = MatchConfig::current();

        return response()->json([
            'config' => $config,
            'locked' => \App\Models\Prediction::exists(),
        ]);
    }

    public function updateMatchConfig(Request $request): JsonResponse
    {
        $data = $request->validate([
            'home_team' => 'required|string|max:80|different:away_team',
            'away_team' => 'required|string|max:80',
            'home_squad' => 'required|array|min:1|max:30',
            'home_squad.*' => 'required|string|max:100|distinct',
            'away_squad' => 'required|array|min:1|max:30',
            'away_squad.*' => 'required|string|max:100|distinct',
            'kickoff_at' => 'nullable|date',
            'venue' => 'nullable|string|max:120',
            'force' => 'sometimes|boolean',
        ]);

        if (array_intersect($data['home_squad'], $data['away_squad'])) {
            return response()->json(['message' => 'A player cannot appear in both squads.'], 422);
        }

        if (\App\Models\Prediction::exists() && !($data['force'] ?? false)) {
            return response()->json([
                'message' => 'Match configuration is locked because predictions already exist.',
                'requires_confirmation' => true,
            ], 409);
        }

        unset($data['force']);
        $data['home_squad'] = array_values(array_map('trim', $data['home_squad']));
        $data['away_squad'] = array_values(array_map('trim', $data['away_squad']));

        $config = MatchConfig::current();
        $config->update($data);
        EventAudit::record('match.configuration_updated', $config, [
            'home_team' => $config->home_team,
            'away_team' => $config->away_team,
            'forced_after_predictions' => \App\Models\Prediction::exists(),
        ]);

        return response()->json(['config' => $config->fresh()]);
    }

    public function listAudits(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->integer('limit', 50), 10), 100);

        return response()->json([
            'data' => EventAudit::query()
                ->latest('id')
                ->limit($limit)
                ->get()
                ->map(fn (EventAudit $audit) => [
                    'id' => $audit->id,
                    'action' => $audit->action,
                    'subject_type' => $audit->subject_type
                        ? class_basename($audit->subject_type)
                        : null,
                    'subject_id' => $audit->subject_id,
                    'context' => $audit->context ?? [],
                    'admin_ip' => $audit->admin_ip,
                    'created_at' => $audit->created_at?->toIso8601String(),
                ]),
        ]);
    }

    // ── Phase control ─────────────────────────────────────────────────────────

    public function setPhase(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phase' => 'required|in:lobby,predictions_open,predictions_closed,trivia_complete,prediction_reveal',
        ]);

        EventState::setCurrent(['phase' => $data['phase']]);
        EventAudit::record('phase.changed', null, ['phase' => $data['phase']]);

        return response()->json(['phase' => $data['phase']]);
    }

    // ── Question management ───────────────────────────────────────────────────

    public function listQuestions(): JsonResponse
    {
        return response()->json(
            Question::orderBy('order_index')->get()
        );
    }

    public function storeQuestion(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_index'      => 'required|integer',
            'type'             => 'required|in:multiple_choice,true_false',
            'text'             => 'required|string',
            'category'         => 'required|in:general_knowledge,fifa_world_cup,visa,mpesa,campaign',
            'options'          => 'required|array|min:2|max:4',
            'options.*'        => 'required|string|max:255|distinct',
            'correct_answer'   => ['required', 'string', Rule::in($request->input('options', []))],
            'duration_seconds' => 'required|integer|min:5|max:120',
            'is_double_points' => 'boolean',
        ]);

        return response()->json(Question::create($data), 201);
    }

    public function updateQuestion(Request $request, Question $question): JsonResponse
    {
        if ($question->status === 'live') {
            return response()->json(['message' => 'Cannot edit a live question.'], 422);
        }

        $data = $request->validate([
            'order_index'      => 'integer',
            'category'         => 'in:general_knowledge,fifa_world_cup,visa,mpesa,campaign',
            'type'             => 'in:multiple_choice,true_false',
            'text'             => 'string',
            'options'          => 'array|min:2|max:4',
            'options.*'        => 'required|string|max:255|distinct',
            'correct_answer'   => ['string', Rule::in($request->input('options', $question->options))],
            'duration_seconds' => 'integer|min:5|max:120',
            'is_double_points' => 'boolean',
        ]);

        $question->update($data);

        return response()->json($question->fresh());
    }

    public function updateQuestionDuration(Request $request, Question $question): JsonResponse
    {
        $data = $request->validate([
            'duration_seconds' => 'required|integer|min:5|max:120',
            'restart_live' => 'sometimes|boolean',
        ]);

        if ($question->status === 'live' && !($data['restart_live'] ?? false)) {
            return response()->json(['message' => 'Confirm restart_live to change a live countdown.'], 422);
        }

        $changes = ['duration_seconds' => $data['duration_seconds']];
        if ($question->status === 'live') {
            $changes['activated_at'] = now();
        }
        $question->update($changes);
        Cache::forget('public-event-state-v2');
        EventAudit::record('question.duration_updated', $question, [
            'duration_seconds' => $question->duration_seconds,
            'live_timer_restarted' => $question->status === 'live',
        ]);

        return response()->json($question->fresh());
    }

    public function destroyQuestion(Question $question): JsonResponse
    {
        if ($question->status === 'live') {
            return response()->json(['message' => 'Cannot delete a live question.'], 422);
        }
        $question->delete();
        return response()->json(['deleted' => true]);
    }

    public function activateQuestion(Request $request, Question $question): JsonResponse
    {
        DB::transaction(function () use ($question) {
            Question::where('status', 'live')->where('id', '!=', $question->id)->update(['status' => 'closed']);
            $question->update(['status' => 'live', 'activated_at' => now()]);
            EventState::setCurrent([
                'phase'               => 'trivia_live',
                'current_question_id' => $question->id,
            ]);
            EventAudit::record('question.activated', $question, ['text' => $question->text]);
        });

        return response()->json($question->fresh());
    }

    public function closeQuestion(Question $question): JsonResponse
    {
        if ($question->status !== 'live') {
            return response()->json(['message' => 'Only the live question can be revealed.'], 422);
        }

        DB::transaction(function () use ($question) {
            $question->update(['status' => 'closed']);
            EventState::setCurrent([
                'phase' => 'trivia_reveal',
                'current_question_id' => $question->id,
            ]);
            EventAudit::record('question.revealed', $question, ['correct_answer' => $question->correct_answer]);
        });

        return response()->json(['status' => 'closed']);
    }

    public function invalidateQuestion(Request $request, Question $question): JsonResponse
    {
        if (EventAudit::where('action', 'question.invalidated')
            ->where('subject_type', Question::class)
            ->where('subject_id', $question->id)
            ->exists()) {
            return response()->json(['message' => 'This question has already been invalidated.'], 422);
        }

        $data = $request->validate([
            'reason' => 'required|string|min:5|max:255',
        ]);

        $summary = DB::transaction(function () use ($question, $data) {
            $answers = Answer::where('question_id', $question->id)->lockForUpdate()->get();
            $pointsReversed = 0;

            foreach ($answers->groupBy('player_id') as $playerId => $playerAnswers) {
                $points = (int) $playerAnswers->sum('points_awarded');
                $correct = $playerAnswers->where('is_correct', true)->count();
                $doubleCorrect = $question->is_double_points ? $correct : 0;
                $player = Player::lockForUpdate()->find($playerId);
                if (!$player) continue;

                $player->update([
                    'trivia_score' => max(0, $player->trivia_score - $points),
                    'trivia_correct_count' => max(0, $player->trivia_correct_count - $correct),
                    'trivia_double_correct' => max(0, $player->trivia_double_correct - $doubleCorrect),
                    'trivia_streak' => 0,
                ]);
                $pointsReversed += $points;
            }

            $question->update(['status' => 'skipped']);
            $state = EventState::current();
            if ($state->current_question_id === $question->id) {
                EventState::setCurrent(['phase' => 'trivia_live', 'current_question_id' => null]);
            }

            EventAudit::record('question.invalidated', $question, [
                'reason' => $data['reason'],
                'answers_affected' => $answers->count(),
                'points_reversed' => $pointsReversed,
            ]);

            return ['answers_affected' => $answers->count(), 'points_reversed' => $pointsReversed];
        });

        return response()->json($summary + ['status' => 'skipped']);
    }

    public function skipQuestion(Question $question): JsonResponse
    {
        $question->update(['status' => 'skipped']);
        EventState::setCurrent(['phase' => 'trivia_live', 'current_question_id' => null]);

        return response()->json(['status' => 'skipped']);
    }

    public function reopenQuestion(Question $question): JsonResponse
    {
        // Reset to draft so admin can re-activate it as live
        $question->update(['status' => 'draft', 'activated_at' => null]);

        return response()->json(['status' => 'draft']);
    }

    // ── Match result + prediction scoring ────────────────────────────────────

    public function setMatchResult(Request $request, ScoringService $scoring): JsonResponse
    {
        $matchConfig = MatchConfig::current();
        $players = $matchConfig->players();
        $data = $request->validate([
            'score_home' => 'required|integer|min:0|max:20',
            'score_away' => 'required|integer|min:0|max:20',
            'scorer'     => 'nullable|string|max:100',
            'potm'       => 'nullable|string|max:100',
        ]);

        $request->validate([
            'scorer' => ['nullable', Rule::in($players)],
            'potm' => ['nullable', Rule::in($players)],
        ]);

        $result = MatchResult::current();
        $result->fill(array_merge($data, ['resolved' => true]))->save();

        // A re-saved (corrected) result must re-score every prediction,
        // so clear the resolved guard before scoring.
        \App\Models\Prediction::query()->update(['resolved' => false]);
        $scored = $scoring->scorePredictions($result);

        EventState::setCurrent(['phase' => 'match_ended']);
        EventAudit::record('predictions.resolved', $result, ['predictions_scored' => $scored]);

        return response()->json([
            'result'          => $result,
            'predictions_scored' => $scored,
        ]);
    }

    // ── Player lookup by phone (for admin score adjustment) ──────────────────

    public function lookupPlayer(Request $request): JsonResponse
    {
        $data   = $request->validate(['phone' => 'required|string|max:20']);
        $phone  = Player::normalisePhone($data['phone']);
        $player = Player::where('phone', $phone)->first();

        if (!$player) {
            return response()->json(['message' => 'Player not found.'], 404);
        }

        return response()->json([
            'id'           => $player->id,
            'nickname'     => $player->nickname,
            'trivia_score' => $player->trivia_score,
        ]);
    }

    // ── Manual score adjustment (with audit) ─────────────────────────────────

    public function adjustScore(Request $request, Player $player): JsonResponse
    {
        $data = $request->validate([
            'adjustment' => 'required|integer',
            'reason'     => 'required|string|max:255',
        ]);

        \Illuminate\Support\Facades\Log::info('Admin score adjustment', [
            'player_id'  => $player->id,
            'nickname'   => $player->nickname,
            'adjustment' => $data['adjustment'],
            'reason'     => $data['reason'],
            'admin_ip'   => $request->ip(),
        ]);

        $previousScore = $player->trivia_score;
        $newScore = max(0, $previousScore + $data['adjustment']);
        $player->update(['trivia_score' => $newScore]);
        EventAudit::record('score.adjusted', $player, [
            'adjustment_requested' => $data['adjustment'],
            'previous_score' => $previousScore,
            'new_score' => $newScore,
            'reason' => $data['reason'],
        ]);

        return response()->json([
            'id'           => $player->id,
            'nickname'     => $player->nickname,
            'trivia_score' => $player->fresh()->trivia_score,
        ]);
    }
}
