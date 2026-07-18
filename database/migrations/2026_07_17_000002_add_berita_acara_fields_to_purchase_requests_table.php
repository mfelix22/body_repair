<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->string('berita_acara_path')->nullable()->after('attachment_path');
            $table->unsignedBigInteger('berita_acara_uploaded_by')->nullable()->after('berita_acara_path');
            $table->timestamp('berita_acara_uploaded_at')->nullable()->after('berita_acara_uploaded_by');

            $table->foreign('berita_acara_uploaded_by')->references('id')->on('users')->nullOnDelete();
        });

        // Also remove berita_acara fields from purchase_orders (they belong on the PPJ/PR)
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['berita_acara_uploaded_by']);
            $table->dropColumn(['berita_acara_path', 'berita_acara_uploaded_by', 'berita_acara_uploaded_at']);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['berita_acara_uploaded_by']);
            $table->dropColumn(['berita_acara_path', 'berita_acara_uploaded_by', 'berita_acara_uploaded_at']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('berita_acara_path')->nullable()->after('printed_by');
            $table->unsignedBigInteger('berita_acara_uploaded_by')->nullable()->after('berita_acara_path');
            $table->timestamp('berita_acara_uploaded_at')->nullable()->after('berita_acara_uploaded_by');
            $table->foreign('berita_acara_uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }
};
