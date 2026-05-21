<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop foreign keys first
        Schema::table('purchase_order_details', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->dropForeign(['uom_id']);
        });

        // Make columns nullable and re-add foreign keys with nullable support
        Schema::table('purchase_order_details', function (Blueprint $table) {
            $table->unsignedBigInteger('item_id')->nullable()->change();
            $table->unsignedBigInteger('uom_id')->nullable()->change();

            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->foreign('uom_id')->references('id')->on('uoms')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to non-nullable
        Schema::table('purchase_order_details', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->dropForeign(['uom_id']);
        });

        Schema::table('purchase_order_details', function (Blueprint $table) {
            $table->unsignedBigInteger('item_id')->change();
            $table->unsignedBigInteger('uom_id')->change();

            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->foreign('uom_id')->references('id')->on('uoms');
        });
    }
};
