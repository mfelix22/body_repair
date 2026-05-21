<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'partial_received' status to receivables enum
        DB::statement("ALTER TABLE receivables MODIFY status ENUM('on_progress','partial_received','completed','cancelled') DEFAULT 'on_progress'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert any partial_received to on_progress
        DB::table('receivables')
            ->where('status', 'partial_received')
            ->update(['status' => 'on_progress']);

        // Remove 'partial_received' from enum
        DB::statement("ALTER TABLE receivables MODIFY status ENUM('on_progress','completed','cancelled') DEFAULT 'on_progress'");
    }
};
