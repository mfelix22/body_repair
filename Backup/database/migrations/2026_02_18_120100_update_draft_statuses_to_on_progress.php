<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Expand enum definitions to include both draft and on_progress
        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM('draft','on_progress','pending','submitted','approved','rejected','completed') DEFAULT 'draft'");
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('draft','on_progress','approved','sent','confirmed','partial','received','cancelled') DEFAULT 'draft'");
        DB::statement("ALTER TABLE work_orders MODIFY status ENUM('draft','on_progress','in_progress','completed','invoiced','cancelled') DEFAULT 'draft'");
        DB::statement("ALTER TABLE receivables MODIFY status ENUM('draft','on_progress','completed') DEFAULT 'draft'");
        DB::statement("ALTER TABLE invoices MODIFY status ENUM('draft','on_progress','sent','paid','partial','overdue','cancelled') DEFAULT 'draft'");

        // Step 2: Migrate draft to on_progress and pending to submitted
        DB::table('purchase_requests')
            ->where('status', 'draft')
            ->update(['status' => 'on_progress']);
        DB::table('purchase_requests')
            ->where('status', 'pending')
            ->update(['status' => 'submitted']);

        $tables = [
            'purchase_orders',
            'work_orders',
            'receivables',
            'invoices',
        ];

        foreach ($tables as $table) {
            DB::table($table)
                ->where('status', 'draft')
                ->update(['status' => 'on_progress']);
        }

        // Step 3: Narrow enum definitions to final set
        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM('on_progress','submitted','approved','rejected','completed') DEFAULT 'on_progress'");
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('on_progress','approved','sent','confirmed','partial','received','cancelled') DEFAULT 'on_progress'");
        DB::statement("ALTER TABLE work_orders MODIFY status ENUM('on_progress','in_progress','completed','invoiced','cancelled') DEFAULT 'on_progress'");
        DB::statement("ALTER TABLE receivables MODIFY status ENUM('on_progress','completed') DEFAULT 'on_progress'");
        DB::statement("ALTER TABLE invoices MODIFY status ENUM('on_progress','sent','paid','partial','overdue','cancelled') DEFAULT 'on_progress'");
    }

    public function down(): void
    {
        // Revert on_progress back to draft and submitted back to pending
        DB::table('purchase_requests')
            ->where('status', 'on_progress')
            ->update(['status' => 'draft']);
        DB::table('purchase_requests')
            ->where('status', 'submitted')
            ->update(['status' => 'pending']);

        $tables = [
            'purchase_orders',
            'work_orders',
            'receivables',
            'invoices',
        ];

        foreach ($tables as $table) {
            DB::table($table)
                ->where('status', 'on_progress')
                ->update(['status' => 'draft']);
        }

        // Restore original enum definitions
        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM('draft','pending','approved','rejected','completed') DEFAULT 'draft'");
        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('draft','sent','confirmed','partial','received','cancelled') DEFAULT 'draft'");
        DB::statement("ALTER TABLE work_orders MODIFY status ENUM('draft','in_progress','completed','invoiced','cancelled') DEFAULT 'draft'");
        DB::statement("ALTER TABLE receivables MODIFY status ENUM('draft','completed') DEFAULT 'draft'");
        DB::statement("ALTER TABLE invoices MODIFY status ENUM('draft','sent','paid','partial','overdue','cancelled') DEFAULT 'draft'");
    }
};
