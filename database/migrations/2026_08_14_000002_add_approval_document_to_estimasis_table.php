<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimasis', function (Blueprint $table) {
            $table->string('approval_document_path', 500)->nullable()->after('notes');
            $table->string('approval_document_name', 255)->nullable()->after('approval_document_path');
        });
    }

    public function down(): void
    {
        Schema::table('estimasis', function (Blueprint $table) {
            $table->dropColumn(['approval_document_path', 'approval_document_name']);
        });
    }
};
