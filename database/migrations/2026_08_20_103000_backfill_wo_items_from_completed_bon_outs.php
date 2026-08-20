<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    /**
     * Backfill WorkOrderItem rows that should have been created when completed
     * Bon Outs contained priced materials (especially spareparts).
     */
    public function up(): void
    {
        $bonOuts = DB::table('bon_outs')
            ->where('status', 'completed')
            ->whereNotNull('work_order_id')
            ->where('bon_out_type', '!=', 3)
            ->get(['id', 'work_order_id']);

        $affectedWos = [];

        foreach ($bonOuts as $bo) {
            $bonItems = DB::table('bon_out_items')
                ->where('bon_out_id', $bo->id)
                ->where('actual_quantity', '>', 0)
                ->where('unit_price', '>', 0)
                ->get([
                    'id',
                    'work_order_item_id',
                    'item_id',
                    'uom_id',
                    'actual_quantity',
                    'unit_price',
                    'remark',
                ]);

            foreach ($bonItems as $boi) {
                if ($boi->work_order_item_id) {
                    // Linked to an original WO demand line — update price if not already set
                    $woItem = DB::table('work_order_items')
                        ->where('id', $boi->work_order_item_id)
                        ->where('work_order_id', $bo->work_order_id)
                        ->first(['id', 'actual_quantity', 'total_price']);

                    if ($woItem && (float) $woItem->total_price == 0) {
                        $total = (float) $woItem->actual_quantity * (float) $boi->unit_price;

                        DB::table('work_order_items')
                            ->where('id', $woItem->id)
                            ->update([
                                'unit_price'  => $boi->unit_price,
                                'total_price' => $total,
                                'updated_at'  => Carbon::now(),
                            ]);

                        $affectedWos[$bo->work_order_id] = true;
                    }
                } else {
                    // Extra material not originally on the WO — create a billed line if missing
                    $expectedTotal = (float) $boi->actual_quantity * (float) $boi->unit_price;

                    $exists = DB::table('work_order_items')
                        ->where('work_order_id', $bo->work_order_id)
                        ->where('item_id', $boi->item_id)
                        ->where('actual_quantity', $boi->actual_quantity)
                        ->where('unit_price', $boi->unit_price)
                        ->where('total_price', $expectedTotal)
                        ->exists();

                    if (!$exists) {
                        DB::table('work_order_items')->insert([
                            'work_order_id'   => $bo->work_order_id,
                            'item_id'         => $boi->item_id,
                            'uom_id'          => $boi->uom_id,
                            'demand_quantity' => $boi->actual_quantity,
                            'actual_quantity' => $boi->actual_quantity,
                            'unit_price'      => $boi->unit_price,
                            'total_price'     => $expectedTotal,
                            'remark'          => $boi->remark,
                            'created_at'      => Carbon::now(),
                            'updated_at'      => Carbon::now(),
                        ]);

                        $affectedWos[$bo->work_order_id] = true;
                    }
                }
            }
        }

        // Recalculate totals for every affected Work Order
        foreach (array_keys($affectedWos) as $woId) {
            $materialTotal = (float) DB::table('work_order_items')
                ->where('work_order_id', $woId)
                ->whereNotNull('total_price')
                ->sum('total_price');

            $laborTotal = (float) DB::table('work_order_labors')
                ->where('work_order_id', $woId)
                ->sum('total_price');

            $grandTotal = $materialTotal + $laborTotal;

            DB::table('work_orders')
                ->where('id', $woId)
                ->update([
                    'material_total' => $materialTotal,
                    'labor_total'    => $laborTotal,
                    'grand_total'    => $grandTotal,
                    'updated_at'     => Carbon::now(),
                ]);
        }
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        // This is a one-time data backfill; a reliable reverse is not practical.
    }
};
