<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM(
            'on_progress','pending_director_approval','approved','sent','confirmed','partial',
            'received','completed','cancelled','closed_shortage','printed'
        ) NOT NULL DEFAULT 'on_progress'");

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('manager_approved_by')->nullable()->after('approved_at')->constrained('users');
            $table->timestamp('manager_approved_at')->nullable()->after('manager_approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Move any pending-director-approval POs back to on_progress before removing the enum value
        DB::statement("UPDATE purchase_orders SET status = 'on_progress' WHERE status = 'pending_director_approval'");

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'manager_approved_by');
            $table->dropColumn(['manager_approved_by', 'manager_approved_at']);
        });

        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM(
            'on_progress','approved','sent','confirmed','partial',
            'received','completed','cancelled','closed_shortage','printed'
        ) NOT NULL DEFAULT 'on_progress'");
    }
};
