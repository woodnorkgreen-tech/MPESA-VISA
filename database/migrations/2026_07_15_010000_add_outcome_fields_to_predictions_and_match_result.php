<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->string('first_scoring_team', 10)->nullable()->after('first_scorer');
            $table->string('halftime_winner', 10)->nullable()->after('first_scoring_team');
            $table->string('fulltime_winner', 10)->nullable()->after('halftime_winner');
        });

        Schema::table('match_result', function (Blueprint $table) {
            $table->unsignedTinyInteger('halftime_score_home')->nullable()->after('score_away');
            $table->unsignedTinyInteger('halftime_score_away')->nullable()->after('halftime_score_home');
            $table->string('first_scoring_team', 10)->nullable()->after('scorer');
        });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropColumn(['first_scoring_team', 'halftime_winner', 'fulltime_winner']);
        });
        Schema::table('match_result', function (Blueprint $table) {
            $table->dropColumn(['halftime_score_home', 'halftime_score_away', 'first_scoring_team']);
        });
    }
};
