<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->timestamp('printed_at')->nullable()->after('status');
            $table->unsignedBigInteger('printed_by')->nullable()->after('printed_at');
            $table->foreign('printed_by')->references('id')->on('users')->nullOnDelete();
        });

        // Migrate existing 'printed' records back to 'completed' and stamp printed_at
        DB::statement("UPDATE receivables SET printed_at = updated_at, status = 'completed' WHERE status = 'printed'");
    }

    public function down(): void
    {
        Schema::table('receivables', function (Blueprint $table) {
            $table->dropForeign(['printed_by']);
            $table->dropColumn(['printed_at', 'printed_by']);
        });
    }
};
