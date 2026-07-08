<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->foreignId('assigned_user_id')->nullable()->after('converted_by_id')->constrained('users')->nullOnDelete();
            $table->string('fulfillment_status', 32)->default('awaiting_assignment')->after('order_status');

            $table->index(['fulfillment_status', 'payment_status']);
            $table->index('assigned_user_id');
        });

        Schema::create('online_sale_order_supplier_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('online_sale_order_id')->constrained('online_sale_orders')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('status', 32)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['online_sale_order_id', 'supplier_id']);
            $table->index(['supplier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_sale_order_supplier_assignments');

        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assigned_user_id');
            $table->dropColumn('fulfillment_status');
        });
    }
};
