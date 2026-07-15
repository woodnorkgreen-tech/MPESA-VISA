<?php

namespace Database\Seeders;

use App\Models\MatchConfig;
use App\Models\SportsTeam;
use Illuminate\Database\Seeder;

class SportsTeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            ['name' => 'England', 'code' => 'ENG', 'country_code' => 'GB', 'players' => [
                ['Jordan Pickford', 'GK', 1], ['Ezri Konsa', 'DF', 2], ["Nico O'Reilly", 'DF', 3], ['Declan Rice', 'MF', 4],
                ['John Stones', 'DF', 5], ['Marc Guehi', 'DF', 6], ['Bukayo Saka', 'FW', 7], ['Elliot Anderson', 'MF', 8],
                ['Harry Kane', 'FW', 9], ['Jude Bellingham', 'MF', 10], ['Marcus Rashford', 'FW', 11], ['Trevoh Chalobah', 'DF', 12],
                ['Dean Henderson', 'GK', 13], ['Jordan Henderson', 'MF', 14], ['Dan Burn', 'DF', 15], ['Kobbie Mainoo', 'MF', 16],
                ['Morgan Rogers', 'MF', 17], ['Anthony Gordon', 'FW', 18], ['Ollie Watkins', 'FW', 19], ['Noni Madueke', 'FW', 20],
                ['Eberechi Eze', 'MF', 21], ['Ivan Toney', 'FW', 22], ['James Trafford', 'GK', 23], ['Reece James', 'DF', 24],
                ['Djed Spence', 'DF', 25], ['Jarell Quansah', 'DF', 26],
            ]],
            ['name' => 'Argentina', 'code' => 'ARG', 'country_code' => 'AR', 'players' => [
                ['Juan Musso', 'GK', 1], ['Marcos Senesi', 'DF', 2], ['Nicolas Tagliafico', 'DF', 3], ['Gonzalo Montiel', 'DF', 4],
                ['Leandro Paredes', 'MF', 5], ['Lisandro Martinez', 'DF', 6], ['Rodrigo De Paul', 'MF', 7], ['Valentin Barco', 'MF', 8],
                ['Julian Alvarez', 'FW', 9], ['Lionel Messi', 'FW', 10], ['Giovani Lo Celso', 'MF', 11], ['Geronimo Rulli', 'GK', 12],
                ['Cristian Romero', 'DF', 13], ['Exequiel Palacios', 'MF', 14], ['Nico Gonzalez', 'MF', 15], ['Thiago Almada', 'FW', 16],
                ['Giuliano Simeone', 'FW', 17], ['Nico Paz', 'FW', 18], ['Nicolas Otamendi', 'DF', 19], ['Alexis Mac Allister', 'MF', 20],
                ['Jose Manuel Lopez', 'FW', 21], ['Lautaro Martinez', 'FW', 22], ['Emiliano Martinez', 'GK', 23], ['Enzo Fernandez', 'MF', 24],
                ['Facundo Medina', 'DF', 25], ['Nahuel Molina', 'DF', 26],
            ]],
        ];

        foreach ($teams as $teamData) {
            $players = $teamData['players'];
            unset($teamData['players']);
            $team = SportsTeam::updateOrCreate(['name' => $teamData['name']], $teamData);
            foreach ($players as [$name, $position, $shirtNumber]) {
                $team->players()->updateOrCreate(['name' => $name], [
                    'position' => $position,
                    'shirt_number' => $shirtNumber,
                    'active' => true,
                ]);
            }
        }

        $england = SportsTeam::where('code', 'ENG')->with('players')->firstOrFail();
        $argentina = SportsTeam::where('code', 'ARG')->with('players')->firstOrFail();

        MatchConfig::current()->update([
            'home_team' => 'England',
            'away_team' => 'Argentina',
            'home_squad' => $england->players->pluck('name')->values()->all(),
            'away_squad' => $argentina->players->pluck('name')->values()->all(),
            // The application timezone is Nairobi; FIFA lists 15:00 Atlanta / 22:00 Nairobi.
            'kickoff_at' => '2026-07-15 22:00:00',
            'venue' => 'Atlanta Stadium',
        ]);
    }
}
