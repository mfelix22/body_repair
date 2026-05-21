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
            // Make item_id nullable to allow custom services
            $table->foreignId('item_id')->nullable()->change();

            // Add service_description for manual service entries
            $table->text('service_description')->nullable()->after('item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_request_details', function (Blueprint $table) {
            // Revert item_id back to not nullable
            $table->foreignId('item_id')->nullable(false)->change();

            // Drop service_description column
            $table->dropColumn('service_description');
        });
    }
};
