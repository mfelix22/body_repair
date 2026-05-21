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
        Schema::table('purchase_request_details', function (Blueprint $table) {
            // Make uom_id nullable to allow services without units
            $table->foreignId('uom_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_request_details', function (Blueprint $table) {
            // Revert uom_id back to not nullable
            $table->foreignId('uom_id')->nullable(false)->change();
        });
    }
};
