<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('questions')->update(['duration_seconds' => 15]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE questions MODIFY duration_seconds INT UNSIGNED NOT NULL DEFAULT 15');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE questions MODIFY duration_seconds INT UNSIGNED NOT NULL DEFAULT 30');
        }
    }
};
