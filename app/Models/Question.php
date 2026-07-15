<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'order_index', 'category', 'type', 'text', 'options',
        'correct_answer', 'duration_seconds',
        'is_double_points', 'status', 'activated_at',
    ];

    protected $casts = [
        'options' => 'array',
        'is_double_points' => 'boolean',
        'activated_at' => 'datetime',
    ];

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function secondsRemaining(): int
    {
        if (!$this->activated_at || $this->status !== 'live') {
            return $this->duration_seconds;
        }
        $elapsed = now()->diffInSeconds($this->activated_at, false) * -1;
        return max(0, $this->duration_seconds - (int) abs($elapsed));
    }
}
