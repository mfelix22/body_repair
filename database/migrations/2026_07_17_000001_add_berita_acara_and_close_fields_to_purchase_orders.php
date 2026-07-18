<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('berita_acara_path')->nullable()->after('printed_by');
            $table->unsignedBigInteger('berita_acara_uploaded_by')->nullable()->after('berita_acara_path');
            $table->timestamp('berita_acara_uploaded_at')->nullable()->after('berita_acara_uploaded_by');
            $table->unsignedBigInteger('closed_by')->nullable()->after('berita_acara_uploaded_at');
            $table->timestamp('closed_at')->nullable()->after('closed_by');

            $table->foreign('berita_acara_uploaded_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['berita_acara_uploaded_by']);
            $table->dropForeign(['closed_by']);
            $table->dropColumn([
                'berita_acara_path',
                'berita_acara_uploaded_by',
                'berita_acara_uploaded_at',
                'closed_by',
                'closed_at',
            ]);
        });
    }
};
