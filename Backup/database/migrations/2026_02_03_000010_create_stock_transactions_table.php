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
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->enum('transaction_type', ['in', 'out', 'adjustment'])->comment('in=receive, out=release, adjustment=manual');
            $table->decimal('quantity', 15, 2)->comment('Always in smallest UOM. Positive for IN, negative for OUT');
            $table->decimal('balance_after', 15, 2)->comment('Stock balance after this transaction');
            $table->string('location', 100)->nullable();
            $table->string('reference_type', 50)->nullable()->comment('E.g., PO, WorkOrder, Adjustment');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('ID of the reference document');
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
        Schema::dropIfExists('stock_transactions');
    }
};
