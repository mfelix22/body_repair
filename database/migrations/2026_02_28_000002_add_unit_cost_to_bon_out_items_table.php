<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bon_out_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 14, 2)->default(0)->after('actual_quantity')
                ->comment('Avg cost per unit at time of Bon Out completion (for COGS)');
        });
    }

    public function down(): void
    {
        Schema::table('bon_out_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
