<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table): void {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('sale_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('processed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('return_date');
            $table->decimal('refund_subtotal', 14, 2)->default(0);
            $table->decimal('refund_total', 14, 2)->default(0);
            $table->string('payment_status', 32)->default('unpaid');
            $table->string('payment_method', 32)->default('cash');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('return_date');
            $table->index(['customer_id', 'created_at']);
            $table->index('sale_id');
        });

        Schema::create('sale_return_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
            $table->foreignId('sale_item_id')->nullable()->constrained('sale_items')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('product_name_en');
            $table->string('product_name_ur')->nullable();
            $table->string('brand_name_en')->nullable();
            $table->string('brand_name_ur')->nullable();
            $table->string('unit_label')->nullable();
            $table->string('image_path')->nullable();
            $table->string('sold_quantity')->nullable();
            $table->string('unit_price')->nullable();
            $table->string('return_quantity')->nullable();
            $table->string('return_amount')->nullable();
            $table->timestamps();

            $table->index(['sale_return_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
    }
};
