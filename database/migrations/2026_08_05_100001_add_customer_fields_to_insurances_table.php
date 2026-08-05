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
        Schema::table('insurances', function (Blueprint $table) {
            $table->string('code', 50)->unique()->after('id');
            $table->string('phone', 50)->nullable()->after('name');
            $table->string('email', 100)->nullable()->after('phone');
            $table->text('address')->nullable()->after('email');
            $table->string('npwp', 30)->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurances', function (Blueprint $table) {
            $table->dropColumn(['code', 'phone', 'email', 'address', 'npwp']);
        });
    }
};
