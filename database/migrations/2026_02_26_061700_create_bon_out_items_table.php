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
        Schema::create('bon_out_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bon_out_id')->constrained('bon_outs')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('uom_id')->constrained('uoms');
            $table->decimal('quantity', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bon_out_items');
    }
};
