<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update grand_total from paket_grand_total for all work orders
        DB::table('work_orders')
            ->where('paket_grand_total', '>', 0)
            ->update([
                'grand_total' => DB::raw('paket_grand_total'),
                'labor_total' => 75000,
                'material_total' => 0
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset grand_total to 0 on rollback
        DB::table('work_orders')->update([
            'grand_total' => 0,
            'labor_total' => 0,
            'material_total' => 0
        ]);
    }
};
