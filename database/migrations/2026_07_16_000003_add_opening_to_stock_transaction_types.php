<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->enum('transaction_type', ['in', 'out', 'adjustment', 'opening'])
                ->comment('in=receive, out=release, adjustment=manual, opening=initial balance')
                ->change();
        });

        // Normalize existing opening-balance records so they use the dedicated type
        DB::table('stock_transactions')
            ->where('reference_type', 'OPENING')
            ->where('transaction_type', 'in')
            ->update(['transaction_type' => 'opening']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert opening records back to 'in' before removing the enum value
        DB::table('stock_transactions')
            ->where('transaction_type', 'opening')
            ->update(['transaction_type' => 'in']);

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->enum('transaction_type', ['in', 'out', 'adjustment'])
                ->comment('in=receive, out=release, adjustment=manual')
                ->change();
        });
    }
};
