<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimasi_items', function (Blueprint $table) {
            // true  = sparepart is supplied by the Insurance
            // false = sparepart is taken from our own storage/stock
            $table->boolean('is_supply')->default(false)->after('item_id');
        });
    }

    public function down(): void
    {
        Schema::table('estimasi_items', function (Blueprint $table) {
            $table->dropColumn('is_supply');
        });
    }
};
