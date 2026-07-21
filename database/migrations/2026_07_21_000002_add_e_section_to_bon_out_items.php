<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE bon_out_items MODIFY bon_out_section ENUM('A','B','C','D','E') NULL COMMENT 'A=Dempul, B=Cat, C=Vernis, D=Poles dan Kebersihan Akhir, E=Sparepart'");
    }

    public function down(): void
    {
        DB::table('bon_out_items')->where('bon_out_section', 'E')->update(['bon_out_section' => null]);

        DB::statement("ALTER TABLE bon_out_items MODIFY bon_out_section ENUM('A','B','C','D') NULL COMMENT 'A=Dempul, B=Cat, C=Vernis, D=Poles dan Kebersihan Akhir'");
    }
};
