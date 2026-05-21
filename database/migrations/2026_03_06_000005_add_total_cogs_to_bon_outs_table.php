<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bon_outs', function (Blueprint $table) {
            $table->decimal('total_cogs', 15, 2)->default(0)
                ->comment('Total Cost of Goods Sold (for all Bon Out types, especially standalone adjustments)')
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bon_outs', function (Blueprint $table) {
            $table->dropColumn('total_cogs');
        });
    }
};
