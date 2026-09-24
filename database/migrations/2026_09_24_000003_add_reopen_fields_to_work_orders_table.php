<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit trail for reopening a completed Work Order (e.g. a forgotten panel
     * needs to be added). Reopening moves the WO back to in_progress.
     */
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('reopened_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable()->after('reopened_by');
            $table->text('reopen_reason')->nullable()->after('reopened_at');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reopened_by');
            $table->dropColumn(['reopened_at', 'reopen_reason']);
        });
    }
};
