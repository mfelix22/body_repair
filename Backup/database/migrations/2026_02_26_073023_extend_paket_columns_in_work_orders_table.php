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
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('paket_code', 200)->nullable()->change();
            $table->string('paket_name', 500)->nullable()->change();
            $table->string('paket_size', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('paket_code', 20)->nullable()->change();
            $table->string('paket_name', 100)->nullable()->change();
            $table->string('paket_size', 30)->nullable()->change();
        });
    }
};
