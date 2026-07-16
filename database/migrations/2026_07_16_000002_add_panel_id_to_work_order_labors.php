<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate panel-type labor master records into the new panels table
        $panelLabors = DB::table('labors')
            ->where('labor_code', 'like', 'PNL-%')
            ->get();

        foreach ($panelLabors as $pl) {
            DB::table('panels')->updateOrInsert(
                ['panel_code' => $pl->labor_code],
                [
                    'description'    => $pl->description,
                    'price'          => $pl->price,
                    'multiplier'       => $pl->multiplier,
                    'price_0_300'    => $pl->price_0_300,
                    'price_300_500'  => $pl->price_300_500,
                    'price_500_800'  => $pl->price_500_800,
                    'price_800_2000' => $pl->price_800_2000,
                    'is_active'      => $pl->is_active,
                    'created_at'     => $pl->created_at,
                    'updated_at'     => $pl->updated_at,
                ]
            );
        }

        Schema::table('work_order_labors', function (Blueprint $table) {
            $table->foreignId('panel_id')
                ->nullable()
                ->after('labor_id')
                ->constrained('panels')
                ->nullOnDelete();
        });

        // Point existing WO labors that referenced panel-type masters to the new panels table
        DB::statement("
            UPDATE work_order_labors
            SET panel_id = (
                SELECT p.id
                FROM panels p
                JOIN labors l ON p.panel_code = l.labor_code
                WHERE l.id = work_order_labors.labor_id
            )
            WHERE labor_id IN (
                SELECT id FROM labors WHERE labor_code LIKE 'PNL-%'
            )
        ");

        // Panel rows are the base line items, not extra labors
        DB::table('work_order_labors')
            ->whereNotNull('panel_id')
            ->update(['labor_id' => null, 'is_extra' => false]);

        // Hide the legacy PNL-* rows in the labor master so only general labors remain visible there
        DB::table('labors')
            ->where('labor_code', 'like', 'PNL-%')
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        // Restore labor_id for rows that were migrated to panels
        DB::statement("
            UPDATE work_order_labors
            SET labor_id = (
                SELECT l.id
                FROM labors l
                JOIN panels p ON p.panel_code = l.labor_code
                WHERE p.id = work_order_labors.panel_id
            )
            WHERE panel_id IS NOT NULL
        ");

        Schema::table('work_order_labors', function (Blueprint $table) {
            $table->dropForeign(['panel_id']);
            $table->dropColumn('panel_id');
        });
    }
};
