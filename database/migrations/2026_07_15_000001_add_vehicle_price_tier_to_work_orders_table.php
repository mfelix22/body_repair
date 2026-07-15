<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('vehicle_price_tier', 20)->nullable()->after('vehicle_km')
                ->comment('0_300 | 300_500 | 500_800 | 800_2000');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn('vehicle_price_tier');
        });
    }
};
