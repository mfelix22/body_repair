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
        Schema::table('receivables', function (Blueprint $table) {
            // Drop existing foreign key
            $table->dropForeign(['purchase_order_id']);

            // Make purchase_order_id nullable (for types 2 & 3)
            $table->foreignId('purchase_order_id')->nullable()->change()
                ->constrained('purchase_orders')->onDelete('set null');

            // Add supplier fields for non-PO Bon In (types 2 & 3)
            $table->foreignId('supplier_id')->nullable()->after('purchase_order_id')
                ->constrained('suppliers')->onDelete('set null');
            $table->string('supplier_name')->nullable()->after('supplier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            // Remove supplier fields
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'supplier_name']);

            // Restore purchase_order_id as required
            $table->dropForeign(['purchase_order_id']);
            $table->foreignId('purchase_order_id')->nullable(false)->change()
                ->constrained('purchase_orders')->onDelete('cascade');
        });
    }
};
