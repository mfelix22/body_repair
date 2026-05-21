<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'completed' to the status enum for purchase_orders
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('on_progress','approved','sent','confirmed','partial','received','completed','cancelled') DEFAULT 'on_progress'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'completed' from the status enum
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('on_progress','approved','sent','confirmed','partial','received','cancelled') DEFAULT 'on_progress'");
    }
};
