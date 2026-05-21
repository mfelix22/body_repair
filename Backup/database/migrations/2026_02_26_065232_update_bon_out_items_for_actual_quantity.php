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
        Schema::table('bon_out_items', function (Blueprint $table) {
            $table->renameColumn('quantity', 'demand_quantity');
            $table->decimal('actual_quantity', 15, 2)->nullable()->after('demand_quantity')
                ->comment('Actual quantity consumed (filled during Bon Out completion)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bon_out_items', function (Blueprint $table) {
            $table->dropColumn('actual_quantity');
            $table->renameColumn('demand_quantity', 'quantity');
        });
    }
};
