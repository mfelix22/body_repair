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
        Schema::table('purchase_order_details', function (Blueprint $table) {
            $table->decimal('discount', 15, 2)->default(0)->after('total_price')->comment('Discount nominal amount');
            $table->decimal('discount_percentage', 15, 2)->default(0)->after('discount')->comment('Discount percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_details', function (Blueprint $table) {
            $table->dropColumn(['discount', 'discount_percentage']);
        });
    }
};
