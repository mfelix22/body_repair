<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bon_out_items', function (Blueprint $table) {
            // Track which WO item this line came from (null = extra item added by SA)
            $table->foreignId('work_order_item_id')
                ->nullable()
                ->after('bon_out_id')
                ->constrained('work_order_items')
                ->nullOnDelete();

            // Selling price charged to customer for extra (non-WO) items
            $table->decimal('unit_price', 15, 2)->nullable()->after('unit_cost');
        });
    }

    public function down(): void
    {
        Schema::table('bon_out_items', function (Blueprint $table) {
            $table->dropForeign(['work_order_item_id']);
            $table->dropColumn(['work_order_item_id', 'unit_price']);
        });
    }
};
