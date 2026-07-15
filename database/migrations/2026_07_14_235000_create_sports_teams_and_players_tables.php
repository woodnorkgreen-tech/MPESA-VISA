<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sports_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('code', 3)->nullable()->unique();
            $table->string('country_code', 2)->nullable();
            $table->timestamps();
        });

        Schema::create('sports_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sports_team_id')->constrained('sports_teams')->cascadeOnDelete();
            $table->string('name', 100);
            $table->enum('position', ['GK', 'DF', 'MF', 'FW'])->nullable();
            $table->unsignedTinyInteger('shirt_number')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['sports_team_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sports_players');
        Schema::dropIfExists('sports_teams');
    }
};
