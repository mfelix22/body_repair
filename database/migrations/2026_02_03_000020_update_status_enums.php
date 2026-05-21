<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update purchase_requests status enum
        DB::statement("ALTER TABLE purchase_requests MODIFY COLUMN status ENUM('draft', 'submitted', 'approved', 'rejected', 'completed') DEFAULT 'draft'");

        // Update work_orders status enum
        DB::statement("ALTER TABLE work_orders MODIFY COLUMN status ENUM('draft', 'in_progress', 'completed', 'invoiced', 'cancelled') DEFAULT 'draft'");
    }

    public function down(): void
    {
        // Revert to old status enums
        DB::statement("ALTER TABLE purchase_requests MODIFY COLUMN status ENUM('draft', 'pending', 'approved', 'rejected', 'completed') DEFAULT 'draft'");

        DB::statement("ALTER TABLE work_orders MODIFY COLUMN status ENUM('draft', 'in_progress', 'completed', 'cancelled') DEFAULT 'draft'");
    }
};
