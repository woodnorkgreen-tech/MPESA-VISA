<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_state', function (Blueprint $table) {
            $table->id();
            $table->string('phase')->default('lobby');
            // lobby | predictions_open | predictions_closed
            // trivia_live | trivia_reveal | trivia_complete
            // match_ended | prediction_reveal
            $table->unsignedBigInteger('current_question_id')->nullable();
            $table->boolean('show_phone_on_screen')->default(false);
            $table->timestamps();
        });

        // Single-row table — seed the initial state immediately
        DB::table('event_state')->insert([
            'phase' => 'lobby',
            'current_question_id' => null,
            'show_phone_on_screen' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('event_state');
    }
};
