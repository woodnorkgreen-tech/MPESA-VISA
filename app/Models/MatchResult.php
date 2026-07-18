<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchResult extends Model
{
    protected $table = 'match_result';

    protected $fillable = [
        'score_home', 'score_away', 'halftime_score_home', 'halftime_score_away',
        'scorer', 'first_scoring_team', 'first_goal_minute', 'potm', 'resolved',
    ];

    protected $casts = [
        'resolved' => 'boolean',
        'first_goal_minute' => 'integer',
    ];

    /**
     * Singleton accessor — the event has exactly one match result.
     * Matches by first row, not a hardcoded id, so it survives resets
     * that advance the auto-increment counter.
     */
    public static function current(): self
    {
        return self::query()->orderBy('id')->first() ?? new self();
    }
}
