<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->unsignedSmallInteger('first_goal_minute')->nullable()->after('first_scoring_team');
            $table->unsignedSmallInteger('first_goal_minute_distance')->nullable()->after('prediction_score');
        });

        Schema::table('match_result', function (Blueprint $table) {
            $table->unsignedSmallInteger('first_goal_minute')->nullable()->after('first_scoring_team');
        });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropColumn(['first_goal_minute', 'first_goal_minute_distance']);
        });

        Schema::table('match_result', function (Blueprint $table) {
            $table->dropColumn('first_goal_minute');
        });
    }
};
