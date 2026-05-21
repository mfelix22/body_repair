<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bon_outs', function (Blueprint $table) {
            $table->id();
            $table->string('bon_out_number')->unique();
            $table->date('issued_date');
            $table->string('issued_to')->nullable()->comment('Person or department receiving items');
            $table->text('purpose')->nullable()->comment('Reason for stock issuance');
            $table->text('notes')->nullable();
            $table->enum('status', ['on_progress', 'completed', 'cancelled'])->default('on_progress');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('completed_by')->nullable()->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bon_outs');
    }
};
