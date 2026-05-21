<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add new vehicle + print fields to work_orders
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('vehicle_merk', 100)->nullable()->after('vehicle_info');
            $table->string('vehicle_type_year', 100)->nullable()->after('vehicle_merk');
            $table->string('chasis_no', 100)->nullable()->after('vehicle_type_year');
            $table->date('deadline')->nullable()->after('work_date');
            $table->enum('account_code', ['C', 'INT_WS', 'INT_W3'])->default('C')->after('customer_id');
            $table->string('sa_sales', 100)->nullable()->after('notes');
        });

        // Add remark to work_order_items
        Schema::table('work_order_items', function (Blueprint $table) {
            $table->string('remark', 255)->nullable()->after('quantity');
        });

        // Add qty to work_order_labors
        Schema::table('work_order_labors', function (Blueprint $table) {
            $table->decimal('qty', 8, 2)->default(1)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['vehicle_merk', 'vehicle_type_year', 'chasis_no', 'deadline', 'account_code', 'sa_sales']);
        });

        Schema::table('work_order_items', function (Blueprint $table) {
            $table->dropColumn('remark');
        });

        Schema::table('work_order_labors', function (Blueprint $table) {
            $table->dropColumn('qty');
        });
    }
};
