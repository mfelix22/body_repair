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
        // Modify the pembayaran enum to include additional values
        DB::statement("ALTER TABLE purchase_orders MODIFY pembayaran ENUM('cash', 'credit', 'tunai', 'non_tunai') DEFAULT 'cash' COMMENT 'Payment method'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE purchase_orders MODIFY pembayaran ENUM('cash', 'credit') DEFAULT 'cash' COMMENT 'Payment method'");
    }
};
