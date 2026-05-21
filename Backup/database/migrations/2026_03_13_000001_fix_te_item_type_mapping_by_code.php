<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix legacy records where TE-coded items were saved as Consumable/default type.
        DB::table('items')
            ->where('code', 'like', 'TE%')
            ->where('item_type', '!=', 'TE')
            ->update(['item_type' => 'TE']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No safe rollback for data correction migration.
    }
};
