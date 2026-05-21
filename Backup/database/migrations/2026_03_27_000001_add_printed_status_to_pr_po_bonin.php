<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE purchase_requests MODIFY COLUMN status ENUM('on_progress','dept_head_approved','gm_approved','rejected','cancelled','completed','printed') NOT NULL DEFAULT 'on_progress'");

        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('on_progress','approved','sent','confirmed','partial','received','completed','cancelled','closed_shortage','printed') NOT NULL DEFAULT 'on_progress'");

        DB::statement("ALTER TABLE receivables MODIFY COLUMN status ENUM('on_progress','partial_received','completed','cancelled','printed') NOT NULL DEFAULT 'on_progress'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE purchase_requests MODIFY COLUMN status ENUM('on_progress','dept_head_approved','gm_approved','rejected','cancelled','completed') NOT NULL DEFAULT 'on_progress'");

        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('on_progress','approved','sent','confirmed','partial','received','completed','cancelled','closed_shortage') NOT NULL DEFAULT 'on_progress'");

        DB::statement("ALTER TABLE receivables MODIFY COLUMN status ENUM('on_progress','partial_received','completed','cancelled') NOT NULL DEFAULT 'on_progress'");
    }
};
