<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->unique()->constrained('players')->cascadeOnDelete();
            $table->unsignedTinyInteger('score_home')->default(0);
            $table->unsignedTinyInteger('score_away')->default(0);
            $table->string('first_scorer', 100);   // player name or "No goal / N/A"
            $table->string('potm', 100);            // player name or "TBD"
            $table->integer('prediction_score')->default(0); // filled in after resolution
            $table->boolean('resolved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
