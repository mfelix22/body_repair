<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('vehicle_plate', 20)->nullable()->after('vehicle_info');
            $table->integer('vehicle_km')->nullable()->after('vehicle_plate');
            $table->string('paket_code', 20)->nullable()->after('vehicle_km');
            $table->string('paket_name', 100)->nullable()->after('paket_code');
            $table->string('paket_size', 30)->nullable()->after('paket_name');
            $table->decimal('paket_grand_total', 15, 2)->default(0)->after('paket_size');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['vehicle_plate', 'vehicle_km', 'paket_code', 'paket_name', 'paket_size', 'paket_grand_total']);
        });
    }
};
