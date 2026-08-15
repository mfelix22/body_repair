<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE items MODIFY item_type ENUM('A','B','C','E','T','TE','SP','AXT','BD') COMMENT 'A=Coating, B=Chemical, C=Consumable, E=Equipment, T=Tools, TE=Tools&Equipment, SP=Sparepart, AXT=Cat, BD=Body'");
    }

    public function down(): void
    {
        DB::table('items')->where('item_type', 'BD')->update(['item_type' => 'AXT']);

        DB::statement("ALTER TABLE items MODIFY item_type ENUM('A','B','C','E','T','TE','SP','AXT') COMMENT 'A=Coating, B=Chemical, C=Consumable, E=Equipment, T=Tools, TE=Tools&Equipment, SP=Sparepart, AXT=Cat'");
    }
};
