<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proforma_invoices', function (Blueprint $table) {
            $table->foreignId('approver3_id')->nullable()->after('approver2_rejected_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approver3_approved_at')->nullable()->after('approver3_id');
            $table->timestamp('approver3_rejected_at')->nullable()->after('approver3_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('proforma_invoices', function (Blueprint $table) {
            $table->dropForeign(['approver3_id']);
            $table->dropColumn(['approver3_id', 'approver3_approved_at', 'approver3_rejected_at']);
        });
    }
};
