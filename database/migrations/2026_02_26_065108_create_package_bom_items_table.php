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
        Schema::create('package_bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('uom_id')->constrained('uoms');
            $table->decimal('quantity', 15, 2)->comment('Base quantity estimate per job');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['package_id', 'item_id'], 'pkg_bom_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_bom_items');
    }
};
