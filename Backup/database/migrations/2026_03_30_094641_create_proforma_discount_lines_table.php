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
        Schema::create('proforma_discount_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_invoice_id')->constrained('proforma_invoices')->cascadeOnDelete();

            // What is being discounted
            $table->enum('target_type', ['package', 'extra_item', 'extra_labor']);
            $table->unsignedBigInteger('target_id')->nullable(); // work_order_items.id or work_order_labors.id
            $table->string('description', 300);

            // Pricing
            $table->decimal('original_price', 15, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('final_price', 15, 2);

            // Per-line approval
            $table->enum('status', ['pending_approval', 'approved', 'rejected'])->default('pending_approval');
            $table->tinyInteger('approvals_required'); // 1 = any-of-3 (<20%), 2 = sequential Mgr/Acc then Director (>=20%)

            // < 20%: 3 slots, any 1 approves
            // >= 20%: 2 slots (approver1=Mgr/Acc, approver2=Director), approver3 unused
            $table->foreignId('approver1_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approver1_approved_at')->nullable();
            $table->timestamp('approver1_rejected_at')->nullable();

            $table->foreignId('approver2_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approver2_approved_at')->nullable();
            $table->timestamp('approver2_rejected_at')->nullable();

            $table->foreignId('approver3_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approver3_approved_at')->nullable();
            $table->timestamp('approver3_rejected_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proforma_discount_lines');
    }
};
