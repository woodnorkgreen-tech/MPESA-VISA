<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('match_config', function (Blueprint $table) {
            $table->id();
            $table->string('home_team', 80)->default('Home Team');
            $table->string('away_team', 80)->default('Away Team');
            $table->json('home_squad');
            $table->json('away_squad');
            $table->timestamp('kickoff_at')->nullable();
            $table->string('venue', 120)->nullable();
            $table->timestamps();
        });

        DB::table('match_config')->insert([
            'id' => 1,
            'home_team' => 'Home Team',
            'away_team' => 'Away Team',
            'home_squad' => json_encode([]),
            'away_squad' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('match_config');
    }
};
