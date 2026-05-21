<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM(
            'on_progress','approved','sent','confirmed','partial',
            'received','completed','cancelled','closed_shortage'
        ) NOT NULL DEFAULT 'on_progress'");
    }

    public function down(): void
    {
        // Update any closed_shortage rows back to received before removing the enum value
        DB::statement("UPDATE purchase_orders SET status = 'received' WHERE status = 'closed_shortage'");

        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM(
            'on_progress','approved','sent','confirmed','partial',
            'received','completed','cancelled'
        ) NOT NULL DEFAULT 'on_progress'");
    }
};
