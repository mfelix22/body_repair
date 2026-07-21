<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE items MODIFY item_type ENUM('A','B','C','E','T','TE','SP') COMMENT 'A=Coating, B=Chemical, C=Consumable, E=Equipment, T=Tools, TE=Tools&Equipment, SP=Sparepart'");
    }

    public function down(): void
    {
        DB::table('items')->where('item_type', 'SP')->update(['item_type' => 'C']);

        DB::statement("ALTER TABLE items MODIFY item_type ENUM('A','B','C','E','T','TE') COMMENT 'A=Coating, B=Chemical, C=Consumable, E=Equipment, T=Tools, TE=Tools&Equipment'");
    }
};
