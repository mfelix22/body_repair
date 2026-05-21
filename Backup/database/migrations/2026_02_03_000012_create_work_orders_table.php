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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('wo_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->date('work_date');
            $table->string('vehicle_info', 200)->nullable()->comment('Vehicle make, model, plate number');
            $table->text('description')->nullable()->comment('What repair work is needed');
            $table->enum('status', ['draft', 'in_progress', 'completed', 'invoiced', 'cancelled'])->default('draft');
            $table->decimal('labor_total', 15, 2)->default(0);
            $table->decimal('material_total', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
