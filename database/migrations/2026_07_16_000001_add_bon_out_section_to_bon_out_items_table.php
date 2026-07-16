<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bon_out_items', function (Blueprint $table) {
            $table->enum('bon_out_section', ['A', 'B', 'C', 'D'])
                ->nullable()
                ->after('remark')
                ->comment('A=Dempul, B=Cat, C=Vernis, D=Poles dan Kebersihan Akhir');
        });
    }

    public function down(): void
    {
        Schema::table('bon_out_items', function (Blueprint $table) {
            $table->dropColumn('bon_out_section');
        });
    }
};
