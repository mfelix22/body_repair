<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->foreignId('dept_head_by')->nullable()->after('require_acknowledgement')->constrained('users')->nullOnDelete();
            $table->timestamp('dept_head_at')->nullable()->after('dept_head_by');
            $table->foreignId('gm_by')->nullable()->after('dept_head_at')->constrained('users')->nullOnDelete();
            $table->timestamp('gm_at')->nullable()->after('gm_by');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['dept_head_by']);
            $table->dropForeign(['gm_by']);
            $table->dropColumn(['dept_head_by', 'dept_head_at', 'gm_by', 'gm_at']);
        });
    }
};
