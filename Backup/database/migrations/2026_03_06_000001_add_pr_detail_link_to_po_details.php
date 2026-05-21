<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds tracking between PR items and PO items,
     * allowing one PR to be split across multiple POs to different suppliers.
     */
    public function up(): void
    {
        Schema::table('purchase_order_details', function (Blueprint $table) {
            // Link PO item back to the original PR item
            $table->foreignId('purchase_request_detail_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('purchase_request_details')
                ->onDelete('set null');
        });

        // Add ordered quantity tracking to PR details
        Schema::table('purchase_request_details', function (Blueprint $table) {
            // Track how much has been ordered across all POs
            $table->decimal('ordered_quantity', 15, 2)
                ->default(0)
                ->after('quantity')
                ->comment('Quantity ordered across all POs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_request_detail_id');
        });

        Schema::table('purchase_request_details', function (Blueprint $table) {
            $table->dropColumn('ordered_quantity');
        });
    }
};
