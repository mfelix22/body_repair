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
        Schema::table('purchase_order_details', function (Blueprint $table) {
            $table->decimal('closed_shortage_quantity', 15, 2)->default(0)->after('received_quantity');
            $table->text('shortage_close_reason')->nullable()->after('closed_shortage_quantity');
            $table->foreignId('shortage_closed_by')->nullable()->after('shortage_close_reason')->constrained('users');
            $table->timestamp('shortage_closed_at')->nullable()->after('shortage_closed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_details', function (Blueprint $table) {
            $table->dropForeign(['shortage_closed_by']);
            $table->dropColumn([
                'closed_shortage_quantity',
                'shortage_close_reason',
                'shortage_closed_by',
                'shortage_closed_at',
            ]);
        });
    }
};
