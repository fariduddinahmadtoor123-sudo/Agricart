<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_sale_order_supplier_assignments', function (Blueprint $table): void {
            $table->timestamp('released_at')->nullable()->after('shipped_at');
            $table->index(['released_at', 'status'], 'ososa_released_status_idx');
        });

        Schema::create('online_sale_order_staff_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('online_sale_order_id')
                ->constrained('online_sale_orders', 'id', 'ososta_order_fk')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users', 'id', 'ososta_user_fk')
                ->cascadeOnDelete();
            $table->string('document', 32)->default('invoice');
            $table->string('status', 32)->default('sent');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['online_sale_order_id', 'user_id'], 'ososta_order_user_unq');
            $table->index(['user_id', 'released_at'], 'ososta_user_released_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_sale_order_staff_assignments');

        Schema::table('online_sale_order_supplier_assignments', function (Blueprint $table): void {
            $table->dropIndex('ososa_released_status_idx');
            $table->dropColumn('released_at');
        });
    }
};
