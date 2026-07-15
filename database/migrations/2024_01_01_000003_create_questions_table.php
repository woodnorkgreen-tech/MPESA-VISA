<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_index')->default(0);
            $table->enum('type', ['multiple_choice', 'true_false'])->default('multiple_choice');
            $table->text('text');
            $table->json('options');           // array of option strings
            $table->string('correct_answer');  // must match one of options exactly
            $table->unsignedInteger('duration_seconds')->default(30);
            $table->boolean('is_double_points')->default(false);
            $table->enum('status', ['draft', 'live', 'closed', 'skipped'])->default('draft');
            $table->timestamp('activated_at')->nullable(); // when question went live (for speed bonus)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
