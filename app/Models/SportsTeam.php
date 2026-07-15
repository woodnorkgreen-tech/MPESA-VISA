<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SportsTeam extends Model
{
    protected $fillable = ['name', 'code', 'country_code'];

    public function players(): HasMany
    {
        return $this->hasMany(SportsPlayer::class)
            ->orderByRaw("CASE position WHEN 'GK' THEN 1 WHEN 'DF' THEN 2 WHEN 'MF' THEN 3 WHEN 'FW' THEN 4 ELSE 5 END")
            ->orderBy('shirt_number')->orderBy('name');
    }
}
