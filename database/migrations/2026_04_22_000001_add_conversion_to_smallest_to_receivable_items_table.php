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
            $table->decimal('conversion_to_smallest', 15, 6)->nullable()->after('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receivable_items', function (Blueprint $table) {
            $table->dropColumn('conversion_to_smallest');
        });
    }
};
