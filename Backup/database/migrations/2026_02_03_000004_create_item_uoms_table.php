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
        Schema::create('item_uoms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->foreignId('uom_id')->constrained('uoms')->onDelete('cascade');
            $table->decimal('conversion_to_smallest', 15, 6)->comment('How many smallest UOM in this UOM. E.g., 1 Box = 100 Pieces');
            $table->decimal('price', 15, 2)->default(0)->comment('Selling price for this UOM');
            $table->boolean('is_default')->default(false)->comment('Default UOM for purchase/display');
            $table->timestamps();

            $table->unique(['item_id', 'uom_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_uoms');
    }
};
