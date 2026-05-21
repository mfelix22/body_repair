<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->after('customer_id')->constrained()->onDelete('set null');
            $table->foreignId('package_size_id')->nullable()->after('package_id')->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropForeign(['package_size_id']);
            $table->dropColumn(['package_id', 'package_size_id']);
        });
    }
};
