<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_cost_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained('stocks')->cascadeOnDelete();
            $table->decimal('old_avg_cost', 15, 2)->default(0);
            $table->decimal('new_avg_cost', 15, 2);
            $table->text('reason');
            $table->foreignId('adjusted_by')->constrained('users');
            $table->timestamps();

            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_cost_adjustments');
    }
};
