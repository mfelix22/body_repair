<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proforma_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('proforma_number', 50)->unique();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->decimal('subtotal', 15, 2);
            $table->enum('discount_type', ['percentage', 'amount'])->nullable();
            $table->decimal('discount_value', 15, 2)->default(0); // raw input value
            $table->decimal('discount_percentage', 5, 2)->default(0); // always computed %
            $table->decimal('discount_amount', 15, 2)->default(0);    // always computed amount
            $table->decimal('total', 15, 2);
            $table->enum('status', ['pending_approval', 'approved', 'rejected'])->default('pending_approval');
            $table->tinyInteger('approvals_required')->default(0); // 0, 1, or 2
            $table->foreignId('approver1_id')->nullable()->constrained('users');
            $table->timestamp('approver1_approved_at')->nullable();
            $table->timestamp('approver1_rejected_at')->nullable();
            $table->foreignId('approver2_id')->nullable()->constrained('users');
            $table->timestamp('approver2_approved_at')->nullable();
            $table->timestamp('approver2_rejected_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proforma_invoices');
    }
};
