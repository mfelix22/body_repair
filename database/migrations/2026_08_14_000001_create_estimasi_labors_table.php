<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimasi_labors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimasi_id')->constrained('estimasis')->cascadeOnDelete();
            $table->foreignId('labor_id')->nullable()->constrained('labors')->nullOnDelete();
            $table->string('description', 200);
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('rate', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimasi_labors');
    }
};
