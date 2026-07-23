<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('online_sale_order_id')->nullable()->constrained('online_sale_orders')->nullOnDelete();
            $table->unsignedBigInteger('online_sale_order_item_id')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->string('status', 20)->default('pending');
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'product_id'], 'product_reviews_customer_product_uq');
            $table->index(['product_id', 'status'], 'product_reviews_product_status_idx');
            $table->index(['status', 'created_at'], 'product_reviews_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
