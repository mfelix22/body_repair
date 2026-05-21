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
        Schema::table('purchase_requests', function (Blueprint $table) {
            // Acknowledge step (e.g., received by purchasing/reviewer)
            $table->foreignId('acknowledged_by')->nullable()->constrained('users');
            $table->timestamp('acknowledged_at')->nullable();

            // Purchasing received step (final step before completion)
            $table->foreignId('purchasing_received_by')->nullable()->constrained('users');
            $table->timestamp('purchasing_received_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'acknowledged_by');
            $table->dropColumn('acknowledged_by');
            $table->dropColumn('acknowledged_at');
            $table->dropForeignIdFor(\App\Models\User::class, 'purchasing_received_by');
            $table->dropColumn('purchasing_received_by');
            $table->dropColumn('purchasing_received_at');
        });
    }
};
