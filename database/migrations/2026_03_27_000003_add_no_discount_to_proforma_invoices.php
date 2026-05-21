<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE proforma_invoices MODIFY COLUMN status ENUM('pending_approval','approved','rejected','no_discount') NOT NULL DEFAULT 'pending_approval'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE proforma_invoices MODIFY COLUMN status ENUM('pending_approval','approved','rejected') NOT NULL DEFAULT 'pending_approval'");
    }
};
