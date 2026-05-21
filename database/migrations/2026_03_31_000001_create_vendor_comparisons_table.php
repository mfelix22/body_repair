<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_comparisons', function (Blueprint $table) {
            $table->id();
            $table->string('comparison_number')->unique();
            $table->unsignedBigInteger('purchase_request_id')->nullable();
            $table->foreign('purchase_request_id')->references('id')->on('purchase_requests')->nullOnDelete();
            $table->string('nomor_permintaan')->nullable();
            $table->date('tanggal');
            $table->text('detail_barang_jasa');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft');
            $table->unsignedBigInteger('selected_vendor_id')->nullable(); // FK set after vendor table created
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('vendor_comparison_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_comparison_id')->constrained('vendor_comparisons')->cascadeOnDelete();
            $table->unsignedTinyInteger('vendor_order'); // 1, 2, 3
            $table->string('nama_calon_vendor');
            $table->text('alamat')->nullable();
            $table->string('telepon_fax')->nullable();
            $table->string('email')->nullable();
            $table->string('pic_contact_person')->nullable();
            $table->string('metode_pembayaran')->nullable(); // tunai / non_tunai / transfer
            $table->string('rekening_bank')->nullable();
            $table->string('term_of_payment')->nullable(); // e.g. "30 hari"
            $table->decimal('harga_barang_jasa', 18, 2)->nullable();
            $table->text('ketentuan_lain')->nullable();
            $table->timestamps();
        });

        // Now add the FK for selected_vendor_id
        Schema::table('vendor_comparisons', function (Blueprint $table) {
            $table->foreign('selected_vendor_id')->references('id')->on('vendor_comparison_vendors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_comparisons', function (Blueprint $table) {
            $table->dropForeign(['selected_vendor_id']);
        });
        Schema::dropIfExists('vendor_comparison_vendors');
        Schema::dropIfExists('vendor_comparisons');
    }
};
