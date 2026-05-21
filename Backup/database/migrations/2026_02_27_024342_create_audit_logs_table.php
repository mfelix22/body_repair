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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type')->comment('Model class name');
            $table->unsignedBigInteger('model_id')->comment('Record ID');
            $table->string('action', 20)->comment('created, updated, deleted');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('User who performed action');
            $table->text('old_values')->nullable()->comment('JSON of old values (for update/delete)');
            $table->text('new_values')->nullable()->comment('JSON of new values (for create/update)');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
