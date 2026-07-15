<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchConfig extends Model
{
    protected $table = 'match_config';

    protected $fillable = [
        'id', 'home_team', 'away_team', 'home_squad', 'away_squad', 'kickoff_at', 'venue',
    ];

    protected $casts = [
        'home_squad' => 'array',
        'away_squad' => 'array',
        'kickoff_at' => 'datetime',
    ];

    public static function current(): self
    {
        return self::firstOrCreate(['id' => 1], [
            'home_team' => 'Home Team',
            'away_team' => 'Away Team',
            'home_squad' => [],
            'away_squad' => [],
        ]);
    }

    public function players(): array
    {
        return array_values(array_unique(array_merge($this->home_squad ?? [], $this->away_squad ?? [])));
    }
}
