<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prediction extends Model
{
    protected $fillable = [
        'player_id', 'score_home', 'score_away',
        'first_scorer', 'first_scoring_team', 'halftime_winner', 'fulltime_winner',
        'potm', 'prediction_score', 'resolved',
    ];

    protected $casts = [
        'resolved' => 'boolean',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
