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
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->renameColumn('quantity', 'demand_quantity');
            $table->foreignId('uom_id')->nullable()->after('item_id')->constrained('uoms')->nullOnDelete();
            $table->decimal('actual_quantity', 15, 2)->nullable()->after('demand_quantity')->comment('Actual quantity used (recorded on Bon Out)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('uom_id');
            $table->dropColumn('actual_quantity');
            $table->renameColumn('demand_quantity', 'quantity');
        });
    }
};
