<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_order_labors', function (Blueprint $table) {
            $table->boolean('is_extra')->default(false)->after('total_price');
        });

        // Backfill: labors added via addLabor (have labor_id + total_price) are extra
        DB::table('work_order_labors')
            ->whereNotNull('labor_id')
            ->whereNotNull('total_price')
            ->update(['is_extra' => true]);
    }

    public function down(): void
    {
        Schema::table('work_order_labors', function (Blueprint $table) {
            $table->dropColumn('is_extra');
        });
    }
};
