<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Approval step by admin/manager
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            // Acknowledgement step
            $table->foreignId('acknowledged_by')->nullable()->constrained('users');
            $table->timestamp('acknowledged_at')->nullable();

            // Receiving step
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->timestamp('received_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'approved_by');
            $table->dropColumn('approved_by');
            $table->dropColumn('approved_at');
            $table->dropForeignIdFor(\App\Models\User::class, 'acknowledged_by');
            $table->dropColumn('acknowledged_by');
            $table->dropColumn('acknowledged_at');
            $table->dropForeignIdFor(\App\Models\User::class, 'received_by');
            $table->dropColumn('received_by');
            $table->dropColumn('received_at');
        });
    }
};
