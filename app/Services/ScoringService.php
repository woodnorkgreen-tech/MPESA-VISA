<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\MatchResult;
use App\Models\Player;
use App\Models\Prediction;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

class ScoringService
{
    // ── Trivia scoring ────────────────────────────────────────────────────────

    /**
     * Score a single answer and persist the result atomically.
     * Returns points awarded (0 on wrong/late).
     */
    public function scoreAnswer(Player $player, Question $question, string $selectedOption, int $responseTimeMs): int
    {
        return DB::transaction(function () use ($player, $question, $selectedOption, $responseTimeMs) {
            Player::whereKey($player->id)->lockForUpdate()->firstOrFail();

            $answer = Answer::updateOrCreate([
                'player_id'        => $player->id,
                'question_id'      => $question->id,
            ], [
                'selected_option'  => $selectedOption,
                'is_correct'       => $selectedOption === $question->correct_answer,
                'response_time_ms' => $responseTimeMs,
                'server_received_at' => now(),
            ]);

            $this->recalculatePlayerTrivia($player);

            return (int) $answer->fresh()->points_awarded;
        });
    }

    /** Rebuild all trivia totals so changing a live answer cannot duplicate points or streaks. */
    public function recalculatePlayerTrivia(Player $player): void
    {
        $answers = Answer::where('player_id', $player->id)
            ->with('question')
            ->get()
            ->sortBy(fn (Answer $answer) => sprintf('%010d-%010d', $answer->question->order_index, $answer->question_id));

        $score = $correctCount = $doubleCorrect = $streak = 0;

        foreach ($answers as $answer) {
            $question = $answer->question;
            $isCorrect = $answer->selected_option === $question->correct_answer;
            $points = 0;

            if ($isCorrect) {
                $streak++;
                $correctCount++;
                $doubleCorrect += $question->is_double_points ? 1 : 0;
                $secondsRemaining = max(0, $question->duration_seconds - (int) ceil($answer->response_time_ms / 1000));
                $points = $this->calculateTriviaPoints(
                    $question->is_double_points,
                    $secondsRemaining,
                    $question->duration_seconds,
                    $streak,
                );
                $score += $points;
            } else {
                $streak = 0;
            }

            $answer->update(['is_correct' => $isCorrect, 'points_awarded' => $points]);
        }

        $player->update([
            'trivia_score' => $score,
            'trivia_streak' => $streak,
            'trivia_correct_count' => $correctCount,
            'trivia_double_correct' => $doubleCorrect,
        ]);
    }

    /**
     * Hardcoded trivia point formula — not admin-configurable.
     *
     * Standard:  800 + speed_bonus(200) + streak_bonus(200 max)
     * Double:   1600 + speed_bonus(400) + streak_bonus(200 max)
     */
    public function calculateTriviaPoints(bool $isDouble, int $secondsRemaining, int $totalSeconds, int $streak): int
    {
        $speedRatio = $totalSeconds > 0 ? $secondsRemaining / $totalSeconds : 0;

        if ($isDouble) {
            $base        = 1600;
            $speedBonus  = (int) round(400 * $speedRatio);
        } else {
            $base        = 800;
            $speedBonus  = (int) round(200 * $speedRatio);
        }

        $streakBonus = $streak >= 2 ? min(200, 50 * ($streak - 1)) : 0;

        return $base + $speedBonus + $streakBonus;
    }

    // ── Prediction scoring ────────────────────────────────────────────────────

    /**
     * Score all unresolved predictions against the stored match result.
     * Idempotent — safe to call multiple times (resolved flag guards re-scoring).
     */
    public function scorePredictions(MatchResult $result): int
    {
        $predictions = Prediction::where('resolved', false)->with('player')->get();

        foreach ($predictions as $prediction) {
            $score = $this->calculatePredictionScore($prediction, $result);
            $firstGoalMinuteDistance = $this->firstGoalMinuteDistance($prediction, $result);

            DB::transaction(function () use ($prediction, $score, $firstGoalMinuteDistance) {
                $prediction->update([
                    'prediction_score' => $score,
                    'first_goal_minute_distance' => $firstGoalMinuteDistance,
                    'resolved' => true,
                ]);
                $prediction->player->update(['prediction_score' => $score]);
            });
        }

        return $predictions->count();
    }

    /**
     * Points stack independently per outcome category.
     *
     * Exact scoreline          500 pts
     * Correct full-time result 200 pts
     * Correct first team       200 pts
     * Correct first scorer     300 pts
     * First goal minute        10 / 7 / 5 / 2 pts
     * Correct half-time result 200 pts
     */
    public function calculatePredictionScore(Prediction $prediction, MatchResult $result): int
    {
        $score = 0;

        // Exact scoreline and full-time result are separate predictions.
        if ($prediction->score_home === $result->score_home && $prediction->score_away === $result->score_away) {
            $score += 500;
        }
        $predictedOutcome = $prediction->fulltime_winner
            ?: $this->matchOutcome($prediction->score_home, $prediction->score_away);
        $actualOutcome = $this->matchOutcome($result->score_home, $result->score_away);
        if ($predictedOutcome === $actualOutcome) {
            $score += 200;
        }

        // First team to score. "None" is valid only for a genuine final 0–0.
        $isGoalless = $result->score_home === 0 && $result->score_away === 0;
        $predictedFirstTeam = $prediction->first_scoring_team;
        if (($predictedFirstTeam === 'none' && $isGoalless)
            || ($predictedFirstTeam && $predictedFirstTeam === $result->first_scoring_team)
            // Compatibility for predictions submitted before the team-based migration.
            || (!$predictedFirstTeam && strtolower(trim($prediction->first_scorer)) === 'no goal / n/a' && $isGoalless)) {
            $score += 200;
        }

        $predictedNoGoal = strtolower(trim($prediction->first_scorer)) === 'no goal / n/a';
        if (($predictedNoGoal && $isGoalless)
            || ($result->scorer && strtolower(trim($prediction->first_scorer)) === strtolower(trim($result->scorer)))) {
            $score += 300;
        }

        $score += $this->firstGoalMinutePoints($prediction, $result);

        if ($prediction->halftime_winner && $result->halftime_score_home !== null && $result->halftime_score_away !== null
            && $prediction->halftime_winner === $this->matchOutcome($result->halftime_score_home, $result->halftime_score_away)) {
            $score += 200;
        }

        return $score;
    }

    // ── Leaderboard ───────────────────────────────────────────────────────────

    /**
     * Tie-break order:
     *  1. Highest total trivia score
     *  2. Highest correct-answer count
     *  3. Fastest average response time (correct answers only)
     *  4. Most double-points questions answered correctly
     */
    public function triviaLeaderboard(int $limit = 10): array
    {
        return Player::select('id', 'nickname', 'trivia_score', 'trivia_correct_count', 'trivia_double_correct')
            ->selectSub(
                Answer::selectRaw('AVG(response_time_ms)')
                    ->whereColumn('player_id', 'players.id')
                    ->where('is_correct', true),
                'avg_response_ms'
            )
            ->orderByDesc('trivia_score')
            ->orderByDesc('trivia_correct_count')
            ->orderBy('avg_response_ms')
            ->orderByDesc('trivia_double_correct')
            ->limit($limit)
            ->get()
            ->map(fn ($p, $i) => [
                'id'             => $p->id,
                'rank'           => $i + 1,
                'nickname'       => $p->nickname,
                'trivia_score'   => $p->trivia_score,
                'correct_count'  => $p->trivia_correct_count,
            ])
            ->toArray();
    }

    public function roundTriviaLeaderboard(string $category, int $limit = 10): array
    {
        $answersByPlayer = Answer::query()
            ->whereHas('question', fn ($query) => $query->where('category', $category))
            ->with('question')
            ->get()
            ->groupBy('player_id');

        return Player::select('id', 'nickname')
            ->get()
            ->map(function (Player $player) use ($answersByPlayer) {
                $answers = ($answersByPlayer->get($player->id) ?? collect())
                    ->sortBy(fn (Answer $answer) => sprintf('%010d-%010d', $answer->question->order_index, $answer->question_id));

                $score = $correctCount = $doubleCorrect = $streak = 0;
                $correctResponseTotal = 0;

                foreach ($answers as $answer) {
                    $question = $answer->question;
                    $isCorrect = $answer->selected_option === $question->correct_answer;

                    if (!$isCorrect) {
                        $streak = 0;
                        continue;
                    }

                    $streak++;
                    $correctCount++;
                    $doubleCorrect += $question->is_double_points ? 1 : 0;
                    $correctResponseTotal += (int) $answer->response_time_ms;
                    $secondsRemaining = max(0, $question->duration_seconds - (int) ceil($answer->response_time_ms / 1000));
                    $score += $this->calculateTriviaPoints(
                        $question->is_double_points,
                        $secondsRemaining,
                        $question->duration_seconds,
                        $streak,
                    );
                }

                return [
                    'id' => $player->id,
                    'nickname' => $player->nickname,
                    'trivia_score' => $score,
                    'correct_count' => $correctCount,
                    'double_correct' => $doubleCorrect,
                    'avg_response_ms' => $correctCount > 0 ? $correctResponseTotal / $correctCount : null,
                ];
            })
            ->sortBy([
                ['trivia_score', 'desc'],
                ['correct_count', 'desc'],
                ['avg_response_ms', 'asc'],
                ['double_correct', 'desc'],
            ])
            ->take($limit)
            ->values()
            ->map(fn (array $entry, int $index) => [
                'id' => $entry['id'],
                'rank' => $index + 1,
                'nickname' => $entry['nickname'],
                'trivia_score' => $entry['trivia_score'],
                'correct_count' => $entry['correct_count'],
            ])
            ->toArray();
    }

    public function playerRoundTriviaScore(Player $player, string $category): int
    {
        $answers = Answer::query()
            ->where('player_id', $player->id)
            ->whereHas('question', fn ($query) => $query->where('category', $category))
            ->with('question')
            ->get()
            ->sortBy(fn (Answer $answer) => sprintf('%010d-%010d', $answer->question->order_index, $answer->question_id));

        $score = $streak = 0;

        foreach ($answers as $answer) {
            $question = $answer->question;
            if ($answer->selected_option !== $question->correct_answer) {
                $streak = 0;
                continue;
            }

            $streak++;
            $secondsRemaining = max(0, $question->duration_seconds - (int) ceil($answer->response_time_ms / 1000));
            $score += $this->calculateTriviaPoints(
                $question->is_double_points,
                $secondsRemaining,
                $question->duration_seconds,
                $streak,
            );
        }

        return $score;
    }

    public function playerRoundTriviaRank(Player $player, string $category): ?int
    {
        $entry = collect($this->roundTriviaLeaderboard($category, PHP_INT_MAX))
            ->first(fn (array $entry) => (int) $entry['id'] === (int) $player->id);

        return $entry ? (int) $entry['rank'] : null;
    }

    public function predictionLeaderboard(int $limit = 10): array
    {
        return Prediction::query()
            ->with('player:id,nickname,prediction_score')
            ->orderByDesc('prediction_score')
            ->orderByRaw('COALESCE(first_goal_minute_distance, 999) ASC')
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn ($prediction, $i) => [
                'id'               => $prediction->player->id,
                'rank'             => $i + 1,
                'nickname'         => $prediction->player->nickname,
                'prediction_score' => $prediction->prediction_score,
                'first_goal_minute' => $prediction->first_goal_minute,
                'first_goal_minute_distance' => $prediction->first_goal_minute_distance,
                'predicted_score'  => "{$prediction->score_home}–{$prediction->score_away}",
                'submitted_at'     => $prediction->created_at?->toIso8601String(),
            ])
            ->toArray();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function matchOutcome(int $home, int $away): string
    {
        return match(true) {
            $home > $away => 'home',
            $away > $home => 'away',
            default       => 'draw',
        };
    }

    private function firstGoalMinutePoints(Prediction $prediction, MatchResult $result): int
    {
        $isGoalless = $result->score_home === 0 && $result->score_away === 0;
        if ($isGoalless) {
            return $prediction->first_scoring_team === 'none' ? 10 : 0;
        }

        $distance = $this->firstGoalMinuteDistance($prediction, $result);
        if ($distance === null) {
            return 0;
        }

        return match (true) {
            $distance === 0 => 10,
            $distance <= 2 => 7,
            $distance <= 5 => 5,
            $distance <= 10 => 2,
            default => 0,
        };
    }

    private function firstGoalMinuteDistance(Prediction $prediction, MatchResult $result): ?int
    {
        $isGoalless = $result->score_home === 0 && $result->score_away === 0;
        if ($isGoalless) {
            return $prediction->first_scoring_team === 'none' ? 0 : 999;
        }

        if (!$prediction->first_goal_minute || !$result->first_goal_minute) {
            return null;
        }

        return abs((int) $prediction->first_goal_minute - (int) $result->first_goal_minute);
    }
}
