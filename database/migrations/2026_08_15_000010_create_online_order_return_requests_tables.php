<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_order_return_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('online_sale_order_id')->constrained('online_sale_orders')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('sale_return_id')->nullable()->constrained('sale_returns')->nullOnDelete();
            $table->string('status', 32)->default('pending');
            $table->text('customer_note')->nullable();
            $table->text('rejection_note')->nullable();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('online_sale_order_id');
        });

        Schema::create('online_order_return_request_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('online_order_return_request_id');
            $table->unsignedBigInteger('online_sale_order_item_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('quantity', 14, 4);
            $table->string('reason', 32);
            $table->string('photo_path')->nullable();
            $table->timestamps();

            $table->foreign('online_order_return_request_id', 'oorri_request_fk')
                ->references('id')
                ->on('online_order_return_requests')
                ->cascadeOnDelete();
            $table->foreign('online_sale_order_item_id', 'oorri_order_item_fk')
                ->references('id')
                ->on('online_sale_order_items')
                ->restrictOnDelete();
            $table->foreign('product_id', 'oorri_product_fk')
                ->references('id')
                ->on('products')
                ->nullOnDelete();

            $table->index(['online_order_return_request_id', 'sort_order'], 'oorri_request_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_order_return_request_items');
        Schema::dropIfExists('online_order_return_requests');
    }
};
