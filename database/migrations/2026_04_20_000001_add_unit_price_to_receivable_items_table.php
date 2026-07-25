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
        Schema::table('receivable_items', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 2)->nullable()->after('quantity_received');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receivable_items', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });
    }
};
