<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['acknowledged_by']);
            $table->dropForeign(['received_by']);
            $table->dropColumn([
                'require_acknowledgement',
                'acknowledged_by',
                'acknowledged_at',
                'received_by',
                'received_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->boolean('require_acknowledgement')->default(true)->after('status');
            $table->foreignId('acknowledged_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable()->after('acknowledged_by');
            $table->foreignId('received_by')->nullable()->after('acknowledged_at')->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable()->after('received_by');
        });
    }
};
