<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE work_orders MODIFY COLUMN account_code ENUM('C','INT_WS','INT_W3','ASURANSI') NOT NULL DEFAULT 'C' AFTER customer_id");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE work_orders MODIFY COLUMN account_code ENUM('C','INT_WS','INT_W3') NOT NULL DEFAULT 'C' AFTER customer_id");
    }
};
