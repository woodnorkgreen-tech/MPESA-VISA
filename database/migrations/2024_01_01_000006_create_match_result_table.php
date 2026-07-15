<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('match_result', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('score_home');
            $table->unsignedTinyInteger('score_away');
            $table->string('scorer', 100)->nullable();  // null = no goal / 0-0
            $table->string('potm', 100)->nullable();    // null = TBD, resolve later
            $table->boolean('resolved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_result');
    }
};
