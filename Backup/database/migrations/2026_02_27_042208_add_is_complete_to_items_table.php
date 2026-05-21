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
        Schema::table('items', function (Blueprint $table) {
            $table->boolean('is_complete')->default(true)->after('is_active')
                ->comment('False for placeholder items from PPB that need completion before Bon In');
            $table->string('code', 50)->unique(false)->change(); // Allow temporary null/duplicate codes for incomplete items
        });

        // Mark all existing items as complete
        DB::table('items')->update(['is_complete' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('is_complete');
        });
    }
};
