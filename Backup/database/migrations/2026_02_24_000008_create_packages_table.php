<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('category', 100); // e.g., "PAKET COATING", "PAKET ALA-CARTE"
            $table->string('code', 20)->unique(); // e.g., "CLS", "SPT", "ELG"
            $table->string('name', 100); // e.g., "Classic Package"
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });

        Schema::create('package_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->string('size_name', 50); // e.g., "Size S", "Size M", "All", "2 Row"
            $table->decimal('price', 15, 2); // Price for this size
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('package_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_sizes');
        Schema::dropIfExists('packages');
    }
};
