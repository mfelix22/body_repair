<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_order_labors', function (Blueprint $table) {
            $table->boolean('is_three_coat')->default(false)->after('is_extra');
            $table->boolean('is_special_repair')->default(false)->after('is_three_coat');
        });
    }

    public function down(): void
    {
        Schema::table('work_order_labors', function (Blueprint $table) {
            $table->dropColumn(['is_three_coat', 'is_special_repair']);
        });
    }
};
