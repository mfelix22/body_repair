<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extra billed WorkOrderItem lines are created when a Bon Out is completed
     * (materials not part of the original WO demand). Track which Bon Out item
     * created each line so cancelling the Bon Out can remove the exact line.
     */
    public function up(): void
    {
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->foreignId('bon_out_item_id')
                ->nullable()
                ->after('work_order_id')
                ->constrained('bon_out_items')
                ->nullOnDelete();
        });

        // Backfill: link existing extra billed lines to the Bon Out item that
        // created them, using the same matching as the earlier backfill.
        $bonOuts = DB::table('bon_outs')
            ->where('status', 'completed')
            ->whereNotNull('work_order_id')
            ->where('bon_out_type', '!=', 3)
            ->get(['id', 'work_order_id']);

        foreach ($bonOuts as $bo) {
            $bonItems = DB::table('bon_out_items')
                ->where('bon_out_id', $bo->id)
                ->whereNull('work_order_item_id')
                ->where('actual_quantity', '>', 0)
                ->where('unit_price', '>', 0)
                ->get(['id', 'item_id', 'actual_quantity', 'unit_price']);

            foreach ($bonItems as $boi) {
                $expectedTotal = (float) $boi->actual_quantity * (float) $boi->unit_price;

                $woItemId = DB::table('work_order_items')
                    ->where('work_order_id', $bo->work_order_id)
                    ->whereNull('bon_out_item_id')
                    ->where('item_id', $boi->item_id)
                    ->where('actual_quantity', $boi->actual_quantity)
                    ->where('unit_price', $boi->unit_price)
                    ->where('total_price', $expectedTotal)
                    ->orderBy('id')
                    ->value('id');

                if ($woItemId) {
                    DB::table('work_order_items')
                        ->where('id', $woItemId)
                        ->update(['bon_out_item_id' => $boi->id]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bon_out_item_id');
        });
    }
};
