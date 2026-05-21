<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE purchase_requests MODIFY status ENUM('on_progress','dept_head_approved','gm_approved','rejected','cancelled','completed') DEFAULT 'on_progress'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE purchase_requests MODIFY status ENUM('on_progress','dept_head_approved','gm_approved','rejected','completed') DEFAULT 'on_progress'"
        );
    }
};
