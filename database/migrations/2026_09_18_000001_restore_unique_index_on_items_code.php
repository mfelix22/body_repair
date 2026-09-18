<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restore the unique index on items.code.
     * The original create_items_table migration defines it, but it was lost
     * on the live database, which allowed duplicate item codes to be inserted.
     *
     * NOTE: run the duplicate-cleanup SQL first; this migration will fail
     * with a "Duplicate entry" error while duplicates still exist.
     */
    public function up(): void
    {
        $exists = DB::select("SHOW INDEX FROM `items` WHERE Key_name = 'items_code_unique'");

        if (empty($exists)) {
            Schema::table('items', function (Blueprint $table) {
                $table->unique('code');
            });
        }
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });
    }
};
