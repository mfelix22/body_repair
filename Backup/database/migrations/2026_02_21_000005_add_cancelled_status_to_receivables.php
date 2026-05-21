<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE receivables MODIFY status ENUM('on_progress','cancelled','completed') DEFAULT 'on_progress'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE receivables MODIFY status ENUM('on_progress','completed') DEFAULT 'on_progress'"
        );
    }
};
