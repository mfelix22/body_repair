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
        Schema::create('purchase_order_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->string('supplier_name')->nullable();
            $table->string('invoice_number', 100);
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('status', ['on_progress', 'paid', 'cancelled'])->default('on_progress');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamp('recorded_at');
            $table->timestamps();
        });

        Schema::create('purchase_order_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_invoice_id')->constrained('purchase_order_invoices')->cascadeOnDelete();
            $table->foreignId('purchase_order_detail_id')->constrained('purchase_order_details');
            $table->decimal('qty_billed', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_invoice_lines');
        Schema::dropIfExists('purchase_order_invoices');
    }
};
