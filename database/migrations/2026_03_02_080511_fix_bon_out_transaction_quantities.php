<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Standardize all outbound transactions (BON_OUT, ADJUSTMENT_OUT) to negative quantities,
     * just like Work Order transactions. This ensures consistency across the system.
     * Both Bon Out and Work Order are outbound movements - they should both be negative.
     */
    public function up(): void
    {
        // Step 1: Flip the sign for BON_OUT and ADJUSTMENT_OUT with positive quantity
        // These were previously stored positive - we're converting them to negative for consistency
        DB::table('stock_transactions')
            ->where('transaction_type', 'out')
            ->whereIn('reference_type', ['BON_OUT', 'ADJUSTMENT_OUT'])
            ->where('quantity', '>', 0)
            ->update(['quantity' => DB::raw('quantity * -1')]);

        // Step 2: Recalculate balance_after for all affected items
        // This ensures the running balance is correct after flipping quantities
        $itemIds = DB::table('stock_transactions')
            ->where('transaction_type', 'out')
            ->whereIn('reference_type', ['BON_OUT', 'ADJUSTMENT_OUT'])
            ->distinct()
            ->pluck('item_id');

        foreach ($itemIds as $itemId) {
            // Get all transactions for this item in chronological order (after flipping)
            $transactions = DB::table('stock_transactions')
                ->where('item_id', $itemId)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc') // Secondary sort by ID for same-second transactions
                ->get();

            // Recalculate running balance
            $balance = 0;
            foreach ($transactions as $trans) {
                $balance += $trans->quantity;
                DB::table('stock_transactions')
                    ->where('id', $trans->id)
                    ->update(['balance_after' => $balance]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Flip the sign back to positive for BON_OUT and ADJUSTMENT_OUT
        DB::table('stock_transactions')
            ->where('transaction_type', 'out')
            ->whereIn('reference_type', ['BON_OUT', 'ADJUSTMENT_OUT'])
            ->where('quantity', '<', 0)
            ->update(['quantity' => DB::raw('quantity * -1')]);

        // Step 2: Recalculate balance_after for all affected items
        $itemIds = DB::table('stock_transactions')
            ->where('transaction_type', 'out')
            ->whereIn('reference_type', ['BON_OUT', 'ADJUSTMENT_OUT'])
            ->distinct()
            ->pluck('item_id');

        foreach ($itemIds as $itemId) {
            $transactions = DB::table('stock_transactions')
                ->where('item_id', $itemId)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $balance = 0;
            foreach ($transactions as $trans) {
                $balance += $trans->quantity;
                DB::table('stock_transactions')
                    ->where('id', $trans->id)
                    ->update(['balance_after' => $balance]);
            }
        }
    }
};
