<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Bon Out type categories (same system as Bon In / Receivable):
     * 1 = Materials used in Workshop (from WO)
     * 2 = Regular purchase (customer buys items directly, no service)
     * 3 = Stock Opname adjustment out
     *
     * Numbering: type * 100000 + sequence
     * e.g., 100001, 100002 (type 1), 200001 (type 2), 300001 (type 3)
     */
    public function up(): void
    {
        Schema::table('bon_outs', function (Blueprint $table) {
            $table->unsignedTinyInteger('bon_out_type')->default(1)->after('bon_out_number');
        });

        // All existing bon outs are type 1 (workshop)
        DB::table('bon_outs')->update(['bon_out_type' => 1]);
    }

    public function down(): void
    {
        Schema::table('bon_outs', function (Blueprint $table) {
            $table->dropColumn('bon_out_type');
        });
    }
};
