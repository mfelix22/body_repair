<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('stock_transactions') || !Schema::hasTable('stocks')) {
            return;
        }

        DB::transaction(function () {
            $woSummary = DB::table('stock_transactions')
                ->select('item_id', 'location', DB::raw('SUM(quantity) as total_qty'))
                ->where('reference_type', 'WO')
                ->where('transaction_type', 'out')
                ->groupBy('item_id', 'location')
                ->get();

            foreach ($woSummary as $summary) {
                if ((float) $summary->total_qty == 0.0) {
                    continue;
                }

                DB::table('stocks')
                    ->where('item_id', $summary->item_id)
                    ->where('location', $summary->location)
                    ->update([
                        'quantity' => DB::raw('quantity - (' . (float) $summary->total_qty . ')'),
                    ]);
            }

            DB::table('stock_transactions')
                ->where('reference_type', 'WO')
                ->where('transaction_type', 'out')
                ->update([
                    'transaction_type' => 'adjustment',
                    'quantity' => 0,
                    'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [Planning only - no stock movement]')"),
                ]);

            $affectedPairs = DB::table('stock_transactions')
                ->where('reference_type', 'WO')
                ->distinct()
                ->select('item_id', 'location')
                ->get();

            foreach ($affectedPairs as $pair) {
                $transactions = DB::table('stock_transactions')
                    ->where('item_id', $pair->item_id)
                    ->where('location', $pair->location)
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                $runningBalance = 0;

                foreach ($transactions as $transaction) {
                    $runningBalance += (float) $transaction->quantity;

                    DB::table('stock_transactions')
                        ->where('id', $transaction->id)
                        ->update(['balance_after' => $runningBalance]);
                }

                DB::table('stocks')
                    ->where('item_id', $pair->item_id)
                    ->where('location', $pair->location)
                    ->update(['quantity' => $runningBalance]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
