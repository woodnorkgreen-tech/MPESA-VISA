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
            ['name' => 'Spain', 'code' => 'ESP', 'country_code' => 'ES', 'players' => [
                ['David Raya', 'GK', 1], ['Marc Pubill', 'DF', 2], ['Alex Grimaldo', 'DF', 3], ['Eric Garcia', 'DF', 4],
                ['Marcos Llorente', 'DF', 5], ['Mikel Merino', 'MF', 6], ['Ferran Torres', 'FW', 7], ['Fabian Ruiz', 'MF', 8],
                ['Gavi', 'MF', 9], ['Dani Olmo', 'FW', 10], ['Yeremy Pino', 'FW', 11], ['Pedro Porro', 'DF', 12],
                ['Joan Garcia', 'GK', 13], ['Aymeric Laporte', 'DF', 14], ['Alex Baena', 'MF', 15], ['Rodri', 'MF', 16],
                ['Nico Williams', 'FW', 17], ['Martin Zubimendi', 'MF', 18], ['Lamine Yamal', 'FW', 19], ['Pedri', 'MF', 20],
                ['Mikel Oyarzabal', 'FW', 21], ['Pau Cubarsi', 'DF', 22], ['Unai Simon', 'GK', 23], ['Marc Cucurella', 'DF', 24],
                ['Victor Munoz', 'FW', 25], ['Borja Iglesias', 'FW', 26],
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

        // This event is dedicated to the final; remove squads left over from earlier rounds.
        SportsTeam::whereNotIn('code', ['ARG', 'ESP'])->delete();

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

        $spain = SportsTeam::where('code', 'ESP')->with('players')->firstOrFail();
        $argentina = SportsTeam::where('code', 'ARG')->with('players')->firstOrFail();

        MatchConfig::current()->update([
            'home_team' => 'Argentina',
            'away_team' => 'Spain',
            'home_squad' => $argentina->players->pluck('name')->values()->all(),
            'away_squad' => $spain->players->pluck('name')->values()->all(),
            // The application timezone is Nairobi; 15:00 New York is 22:00 Nairobi.
            'kickoff_at' => '2026-07-19 22:00:00',
            'venue' => 'New York New Jersey Stadium',
        ]);
    }
}
