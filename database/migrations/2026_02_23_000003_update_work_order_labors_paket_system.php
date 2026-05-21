<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_order_labors', function (Blueprint $table) {
            $table->text('remarks')->nullable()->after('description');
            // Make hours, rate, total_price nullable since pricing is paket-based
            $table->decimal('hours', 8, 2)->nullable()->change();
            $table->decimal('rate', 15, 2)->nullable()->change();
            $table->decimal('total_price', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('work_order_labors', function (Blueprint $table) {
            $table->dropColumn('remarks');
            $table->decimal('hours', 8, 2)->default(0)->change();
            $table->decimal('rate', 15, 2)->change();
            $table->decimal('total_price', 15, 2)->change();
        });
    }
};
