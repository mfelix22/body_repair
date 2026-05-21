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
            $table->boolean('include_ppn')->default(true)->after('notes')->comment('Include PPN 11%');
            $table->enum('pph_type', ['none', 'pph_21', 'pph_23'])->default('none')->after('include_ppn')->comment('PPH Type');
            $table->string('waktu_pengerjaan')->nullable()->after('pph_type')->comment('Work duration (e.g., 30 Hari)');
            $table->enum('pembayaran', ['cash', 'credit'])->default('cash')->after('waktu_pengerjaan')->comment('Payment method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['include_ppn', 'pph_type', 'waktu_pengerjaan', 'pembayaran']);
        });
    }
};
