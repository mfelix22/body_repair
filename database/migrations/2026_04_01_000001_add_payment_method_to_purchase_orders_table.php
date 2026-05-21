<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Add payment_method: the "type" chosen first (Credit / CBD / DP)
            $table->enum('payment_method', ['credit', 'cbd', 'dp'])
                ->nullable()
                ->after('jatuh_tempo')
                ->comment('Payment method type: credit, cbd (Cash Before Delivery), dp (Down Payment)');
        });

        // Ensure pembayaran enum includes tunai and non_tunai
        DB::statement("ALTER TABLE purchase_orders MODIFY pembayaran ENUM('cash','credit','tunai','non_tunai','cicilan') DEFAULT NULL COMMENT 'Tunai or Non-Tunai'");
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
