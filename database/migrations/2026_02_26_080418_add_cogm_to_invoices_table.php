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
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('cogm_material', 15, 2)->nullable()->after('grand_total')->comment('Cost of materials (actual qty × avg cost)');
            $table->decimal('cogm_labor', 15, 2)->nullable()->after('cogm_material')->comment('Fixed labor cost included in COGM');
            $table->decimal('cogm', 15, 2)->nullable()->after('cogm_labor')->comment('Total Cost of Goods Manufactured');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['cogm_material', 'cogm_labor', 'cogm']);
        });
    }
};
