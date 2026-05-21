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
        Schema::table('invoices', function (Blueprint $table) {
            // Add discount_percentage column after discount_amount
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('discount_amount');
        });

        // Migrate existing discount_amount to discount_percentage
        // Calculate percentage from existing amounts
        DB::statement('
            UPDATE invoices 
            SET discount_percentage = CASE 
                WHEN subtotal > 0 THEN ROUND((discount_amount / subtotal) * 100, 2)
                ELSE 0 
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('discount_percentage');
        });
    }
};
