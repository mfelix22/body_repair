<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE items MODIFY item_type ENUM('A','B','C','E','T','TE','SP','AXT') COMMENT 'A=Coating, B=Chemical, C=Consumable, E=Equipment, T=Tools, TE=Tools&Equipment, SP=Sparepart, AXT=Cat'");
    }

    public function down(): void
    {
        DB::table('items')->where('item_type', 'AXT')->update(['item_type' => 'SP']);

        DB::statement("ALTER TABLE items MODIFY item_type ENUM('A','B','C','E','T','TE','SP') COMMENT 'A=Coating, B=Chemical, C=Consumable, E=Equipment, T=Tools, TE=Tools&Equipment, SP=Sparepart'");
    }
};
