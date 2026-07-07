<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_sale_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->date('order_date');
            $table->string('locale', 8)->default('en');
            $table->string('order_status', 32)->default('pending');
            $table->string('payment_status', 32)->default('unpaid');
            $table->string('customer_payment_method', 32)->nullable();
            $table->text('customer_payment_note')->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->string('ship_contact_name')->nullable();
            $table->string('ship_phone')->nullable();
            $table->text('ship_address')->nullable();
            $table->string('ship_city')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('order_date');
            $table->index(['order_status', 'created_at']);
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('online_sale_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('online_sale_order_id')->constrained('online_sale_orders')->cascadeOnDelete();
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

            $table->index(['online_sale_order_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_sale_order_items');
        Schema::dropIfExists('online_sale_orders');
    }
};
