<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SportsPlayer extends Model
{
    protected $fillable = ['sports_team_id', 'name', 'position', 'shirt_number', 'active'];
    protected $casts = ['active' => 'boolean'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(SportsTeam::class, 'sports_team_id');
    }
}
