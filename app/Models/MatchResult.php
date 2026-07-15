<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchResult extends Model
{
    protected $table = 'match_result';

    protected $fillable = [
        'score_home', 'score_away', 'scorer', 'potm', 'resolved',
    ];

    protected $casts = [
        'resolved' => 'boolean',
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
