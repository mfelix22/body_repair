<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

return new class extends Migration
{
    /**
     * Backfill estimasi discount fields on Work Orders whose Estimasi reached
     * a final state ('approved' / 'no_discount') but was never applied.
     * applyToWorkOrder() previously only ran for ASURANSI Work Orders, so
     * approved Estimasis on C / INT_WS / INT_W3 jobs were silently dropped.
     * Iterating by estimasi id ensures the newest final Estimasi wins.
     */
    public function up(): void
    {
        $finalEstimasis = DB::table('estimasis')
            ->whereIn('status', ['approved', 'no_discount'])
            ->orderBy('id')
            ->get([
                'id',
                'work_order_id',
                'panel_discount_percentage',
                'sparepart_discount_percentage',
                'panel_discount_amount',
                'sparepart_discount_amount',
            ]);

        foreach ($finalEstimasis as $est) {
            DB::table('work_orders')
                ->where('id', $est->work_order_id)
                ->where(function ($q) use ($est) {
                    $q->whereNull('active_estimasi_id')
                        ->orWhere('active_estimasi_id', '<', $est->id);
                })
                ->update([
                    'estimasi_discount_percentage_panel'     => $est->panel_discount_percentage,
                    'estimasi_discount_percentage_sparepart' => $est->sparepart_discount_percentage,
                    'estimasi_discount_amount_panel'         => $est->panel_discount_amount,
                    'estimasi_discount_amount_sparepart'     => $est->sparepart_discount_amount,
                    'active_estimasi_id'                     => $est->id,
                    'updated_at'                             => Carbon::now(),
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
