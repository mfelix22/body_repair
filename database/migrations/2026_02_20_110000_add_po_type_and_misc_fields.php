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
            $table->enum('po_type', ['purchase_order', 'service_order'])->default('purchase_order')->after('po_number')->comment('Type: Purchase Order (PPB) or Service Order (PPJ)');
            $table->decimal('misc_cost', 15, 2)->default(0)->after('total_amount')->comment('Lain-lain (shipping, duty stamp, etc)');
            $table->string('misc_cost_description')->nullable()->after('misc_cost')->comment('Description for misc cost');
            $table->string('supplier_contact_person')->nullable()->after('supplier_phone')->comment('UP (Contact Person)');
            $table->string('bank_account')->nullable()->after('pembayaran')->comment('Bank account info');
            $table->string('jatuh_tempo')->nullable()->after('bank_account')->comment('Due date / Jatuh Tempo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['po_type', 'misc_cost', 'misc_cost_description', 'supplier_contact_person', 'bank_account', 'jatuh_tempo']);
        });
    }
};
