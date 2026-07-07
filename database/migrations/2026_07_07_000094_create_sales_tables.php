<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->string('sale_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('sale_date');
            $table->string('payment_status', 32)->default('paid');
            $table->string('payment_method', 32)->default('cash');
            $table->string('price_display', 16)->default('plain');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->string('discount_type', 16)->nullable();
            $table->decimal('discount_value', 14, 2)->nullable();
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->boolean('print_controls')->default(false);
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('completed');
            $table->timestamps();

            $table->index('sale_date');
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('sale_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('product_name_en');
            $table->string('product_name_ur')->nullable();
            $table->string('brand_name_en')->nullable();
            $table->string('brand_name_ur')->nullable();
            $table->string('unit_label')->nullable();
            $table->string('image_path')->nullable();
            $table->string('quantity')->nullable();
            $table->string('unit_price')->nullable();
            $table->string('price_tier_key')->nullable();
            $table->string('line_total')->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
