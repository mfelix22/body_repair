<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->decimal('estimasi_discount_percentage_panel', 5, 2)->default(0)->after('grand_total');
            $table->decimal('estimasi_discount_percentage_sparepart', 5, 2)->default(0)->after('estimasi_discount_percentage_panel');
            $table->decimal('estimasi_discount_amount_panel', 15, 2)->default(0)->after('estimasi_discount_percentage_sparepart');
            $table->decimal('estimasi_discount_amount_sparepart', 15, 2)->default(0)->after('estimasi_discount_amount_panel');
            $table->foreignId('active_estimasi_id')->nullable()->after('estimasi_discount_amount_sparepart')
                ->constrained('estimasis')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_estimasi_id');
            $table->dropColumn([
                'estimasi_discount_percentage_panel',
                'estimasi_discount_percentage_sparepart',
                'estimasi_discount_amount_panel',
                'estimasi_discount_amount_sparepart',
            ]);
        });
    }
};
