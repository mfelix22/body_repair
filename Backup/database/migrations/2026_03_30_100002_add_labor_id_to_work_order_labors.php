<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_order_labors', function (Blueprint $table) {
            // Link to labor master (null = freetext labor from original WO create flow)
            $table->foreignId('labor_id')
                ->nullable()
                ->after('work_order_id')
                ->constrained('labors')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_order_labors', function (Blueprint $table) {
            $table->dropForeign(['labor_id']);
            $table->dropColumn('labor_id');
        });
    }
};
