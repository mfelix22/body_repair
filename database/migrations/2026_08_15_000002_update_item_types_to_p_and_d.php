<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE items MODIFY item_type ENUM('A','B','C','E','T','TE','SP','AXT','BD','P','D') COMMENT 'A=Coating, B=Chemical, C=Consumable, E=Equipment, T=Tools, TE=Tools&Equipment, SP=Sparepart, AXT=Cat, BD=Body, P=Cat, D=Body'");

        DB::table('items')->where('item_type', 'AXT')->update(['item_type' => 'P']);
        DB::table('items')->where('item_type', 'BD')->update(['item_type' => 'D']);

        DB::statement("ALTER TABLE items MODIFY item_type ENUM('A','B','C','E','T','TE','SP','P','D') COMMENT 'A=Coating, B=Chemical, C=Consumable, E=Equipment, T=Tools, TE=Tools&Equipment, SP=Sparepart, P=Cat, D=Body'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE items MODIFY item_type ENUM('A','B','C','E','T','TE','SP','AXT','BD','P','D') COMMENT 'A=Coating, B=Chemical, C=Consumable, E=Equipment, T=Tools, TE=Tools&Equipment, SP=Sparepart, AXT=Cat, BD=Body, P=Cat, D=Body'");

        DB::table('items')->where('item_type', 'P')->update(['item_type' => 'AXT']);
        DB::table('items')->where('item_type', 'D')->update(['item_type' => 'BD']);

        DB::statement("ALTER TABLE items MODIFY item_type ENUM('A','B','C','E','T','TE','SP','AXT','BD') COMMENT 'A=Coating, B=Chemical, C=Consumable, E=Equipment, T=Tools, TE=Tools&Equipment, SP=Sparepart, AXT=Cat, BD=Body'");
    }
};
