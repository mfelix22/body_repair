<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_request_details', function (Blueprint $table) {
            $table->boolean('is_custom_item')->default(false)->after('service_description');
            $table->string('custom_item_name')->nullable()->after('is_custom_item');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_request_details', function (Blueprint $table) {
            $table->dropColumn(['is_custom_item', 'custom_item_name']);
        });
    }
};
