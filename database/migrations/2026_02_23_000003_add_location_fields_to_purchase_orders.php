<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('lokasi_pengerjaan')->nullable()->after('supplier_contact_person')->comment('Work location (for PPJ)');
            $table->string('lokasi_pengiriman')->nullable()->after('lokasi_pengerjaan')->comment('Delivery location (for PPB)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['lokasi_pengerjaan', 'lokasi_pengiriman']);
        });
    }
};
