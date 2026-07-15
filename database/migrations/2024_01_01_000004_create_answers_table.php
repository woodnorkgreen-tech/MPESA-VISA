<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Append-only audit log — never updated after insert
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->string('selected_option');
            $table->boolean('is_correct')->default(false);
            $table->integer('points_awarded')->default(0);
            $table->unsignedInteger('response_time_ms')->nullable(); // for tie-break #3
            $table->timestamp('server_received_at')->useCurrent();

            // One answer per player per question
            $table->unique(['player_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
