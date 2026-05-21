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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->after('payment_terms');
            $table->date('invoice_date')->nullable()->after('invoice_number');
            $table->date('invoice_due_date')->nullable()->after('invoice_date');
            $table->text('invoice_notes')->nullable()->after('invoice_due_date');
            $table->foreignId('invoice_recorded_by')->nullable()->constrained('users')->after('invoice_notes');
            $table->timestamp('invoice_recorded_at')->nullable()->after('invoice_recorded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['invoice_recorded_by']);
            $table->dropColumn([
                'invoice_number',
                'invoice_date',
                'invoice_due_date',
                'invoice_notes',
                'invoice_recorded_by',
                'invoice_recorded_at',
            ]);
        });
    }
};
