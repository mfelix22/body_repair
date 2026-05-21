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
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_uom_id')->constrained('uoms')->onDelete('cascade');
            $table->foreignId('to_uom_id')->constrained('uoms')->onDelete('cascade');
            $table->decimal('conversion_factor', 15, 6)->comment('How many to_uom in one from_uom. Example: 1 Box = 10 Pieces, factor = 10');
            $table->timestamps();

            // Prevent duplicate conversions
            $table->unique(['from_uom_id', 'to_uom_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
