<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prediction extends Model
{
    protected $fillable = [
        'player_id', 'score_home', 'score_away',
        'first_scorer', 'first_scoring_team', 'first_goal_minute',
        'halftime_winner', 'fulltime_winner',
        'potm', 'prediction_score', 'first_goal_minute_distance', 'resolved',
    ];

    protected $casts = [
        'resolved' => 'boolean',
        'first_goal_minute' => 'integer',
        'first_goal_minute_distance' => 'integer',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
