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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Item code/SKU');
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable()->comment('e.g., Paint, Parts, Chemicals');
            $table->foreignId('smallest_uom_id')->constrained('uoms')->comment('The smallest UOM for stock storage');
            $table->decimal('reorder_level', 15, 2)->default(0)->comment('Alert when stock below this level');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
