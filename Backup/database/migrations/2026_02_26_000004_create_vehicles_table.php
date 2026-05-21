<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('plate_number', 20)->unique();
            $table->string('brand', 100)->nullable();      // e.g., Toyota, Mercedes-Benz
            $table->string('model', 100)->nullable();      // e.g., Avanza, E 350
            $table->string('year', 10)->nullable();        // e.g., 2021
            $table->string('color', 50)->nullable();
            $table->string('chasis_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('customer_id')
                ->constrained('vehicles')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropColumn('vehicle_id');
        });
        Schema::dropIfExists('vehicles');
    }
};
