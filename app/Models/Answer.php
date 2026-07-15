<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    public $timestamps = false; // uses server_received_at instead

    protected $fillable = [
        'player_id', 'question_id', 'selected_option',
        'is_correct', 'points_awarded', 'response_time_ms', 'server_received_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'server_received_at' => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
