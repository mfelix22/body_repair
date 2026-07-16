<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panels', function (Blueprint $table) {
            $table->id();
            $table->string('panel_code', 20)->unique();
            $table->string('description', 255);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('multiplier', 6, 2)->nullable();
            $table->decimal('price_0_300', 15, 2)->nullable();
            $table->decimal('price_300_500', 15, 2)->nullable();
            $table->decimal('price_500_800', 15, 2)->nullable();
            $table->decimal('price_800_2000', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panels');
    }
};
