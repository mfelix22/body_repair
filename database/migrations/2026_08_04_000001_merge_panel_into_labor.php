<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reverses the earlier Panel/Labor split: Panel is folded back into the
     * Labor master list as the single source of truth for repair operations
     * chosen on a Work Order. The `panels` table itself is left untouched so
     * historical Work Orders that already reference `panel_id` keep working.
     */
    public function up(): void
    {
        $panels = DB::table('panels')->get();

        foreach ($panels as $panel) {
            DB::table('labors')->updateOrInsert(
                ['labor_code' => $panel->panel_code],
                [
                    'description'    => $panel->description,
                    'price'          => $panel->price,
                    'multiplier'     => 1,
                    'price_0_300'    => $panel->price_0_300,
                    'price_300_500'  => $panel->price_300_500,
                    'price_500_800'  => $panel->price_500_800,
                    'price_800_2000' => $panel->price_800_2000,
                    'is_active'      => $panel->is_active,
                    'updated_at'     => now(),
                    'created_at'     => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // Hide the merged panel rows in Labor again (matches the earlier split migration)
        DB::table('labors')
            ->where('labor_code', 'like', 'PNL-%')
            ->update(['is_active' => false]);
    }
};
