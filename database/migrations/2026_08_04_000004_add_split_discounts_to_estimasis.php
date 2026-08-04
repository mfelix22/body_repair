<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Split the single Estimasi discount into a Panel discount and a
     * Sparepart discount, since these two can have different percentages.
     * The original `discount_percentage` / `discount_amount` columns are kept
     * as the blended (overall) figures used to decide the approval tier.
     */
    public function up(): void
    {
        Schema::table('estimasis', function (Blueprint $table) {
            $table->decimal('panel_subtotal', 15, 2)->default(0)->after('subtotal');
            $table->decimal('panel_discount_percentage', 5, 2)->default(0)->after('panel_subtotal');
            $table->decimal('panel_discount_amount', 15, 2)->default(0)->after('panel_discount_percentage');
            $table->decimal('sparepart_subtotal', 15, 2)->default(0)->after('panel_discount_amount');
            $table->decimal('sparepart_discount_percentage', 5, 2)->default(0)->after('sparepart_subtotal');
            $table->decimal('sparepart_discount_amount', 15, 2)->default(0)->after('sparepart_discount_percentage');
        });

        // Backfill existing rows: treat the whole subtotal as "panel" so historical
        // Estimasis keep their original discount amount/percentage intact.
        DB::table('estimasis')->update([
            'panel_subtotal'            => DB::raw('subtotal'),
            'panel_discount_percentage' => DB::raw('discount_percentage'),
            'panel_discount_amount'     => DB::raw('discount_amount'),
        ]);
    }

    public function down(): void
    {
        Schema::table('estimasis', function (Blueprint $table) {
            $table->dropColumn([
                'panel_subtotal',
                'panel_discount_percentage',
                'panel_discount_amount',
                'sparepart_subtotal',
                'sparepart_discount_percentage',
                'sparepart_discount_amount',
            ]);
        });
    }
};
