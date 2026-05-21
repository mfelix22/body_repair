<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Expand enum to include both old and new values
        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM('on_progress','submitted','approved','dept_head_approved','gm_approved','rejected','completed') DEFAULT 'on_progress'");

        // Step 2: Migrate old values to new values
        DB::table('purchase_requests')
            ->where('status', 'submitted')
            ->update(['status' => 'on_progress']);

        DB::table('purchase_requests')
            ->where('status', 'approved')
            ->update(['status' => 'dept_head_approved']);

        // Step 3: Narrow enum to final set
        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM('on_progress','dept_head_approved','gm_approved','rejected','completed') DEFAULT 'on_progress'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE purchase_requests MODIFY status ENUM('on_progress','submitted','approved','rejected','completed') DEFAULT 'on_progress'");

        DB::table('purchase_requests')
            ->where('status', 'dept_head_approved')
            ->update(['status' => 'approved']);

        DB::table('purchase_requests')
            ->where('status', 'gm_approved')
            ->update(['status' => 'approved']);
    }
};
