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
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('revoked_by')->nullable()->after('purchasing_received_at');
            $table->timestamp('revoked_at')->nullable()->after('revoked_by');
            $table->text('revocation_reason')->nullable()->after('revoked_at');

            $table->foreign('revoked_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['revoked_by']);
            $table->dropColumn(['revoked_by', 'revoked_at', 'revocation_reason']);
        });
    }
};
