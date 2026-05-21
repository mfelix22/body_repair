<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labors', function (Blueprint $table) {
            $table->decimal('multiplier', 6, 2)->nullable()->after('price');
            $table->decimal('price_0_300', 15, 2)->nullable()->after('multiplier');
            $table->decimal('price_300_500', 15, 2)->nullable()->after('price_0_300');
            $table->decimal('price_500_800', 15, 2)->nullable()->after('price_300_500');
            $table->decimal('price_800_2000', 15, 2)->nullable()->after('price_500_800');
        });
    }

    public function down(): void
    {
        Schema::table('labors', function (Blueprint $table) {
            $table->dropColumn(['multiplier', 'price_0_300', 'price_300_500', 'price_500_800', 'price_800_2000']);
        });
    }
};
