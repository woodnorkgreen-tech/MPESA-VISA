<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Data-privacy change: nickname becomes the sole player identity.
     * Phone is no longer collected (kept nullable for legacy rows and the
     * admin simulator); email is no longer collected.
     */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->string('phone', 20)->nullable()->change();
        });

        // Deduplicate nicknames case-insensitively before adding the unique
        // index (suffix legacy duplicates with their row id).
        $seen = [];
        foreach (DB::table('players')->orderBy('id')->get(['id', 'nickname']) as $row) {
            $key = mb_strtolower(trim($row->nickname));
            if (isset($seen[$key])) {
                DB::table('players')->where('id', $row->id)
                    ->update(['nickname' => mb_substr($row->nickname, 0, 43).' #'.$row->id]);
            }
            $seen[$key] = true;
        }

        Schema::table('players', function (Blueprint $table) {
            $table->unique('nickname');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropUnique(['nickname']);
            $table->string('phone', 20)->nullable(false)->change();
            $table->unique('phone');
        });
    }
};
