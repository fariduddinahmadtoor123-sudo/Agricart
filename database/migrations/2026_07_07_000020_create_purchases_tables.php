<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table): void {
            $table->id();
            $table->string('purchase_number')->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('purchaser_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('locale', 8)->default('en');
            $table->date('purchase_date');
            $table->string('delivery_status', 32)->default('pending');
            $table->string('payment_status', 32)->default('pending');
            $table->decimal('supplier_balance_snapshot', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('balance_due', 14, 2)->default(0);
            $table->string('vendor_bill_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('purchase_date');
            $table->index(['supplier_id', 'created_at']);
        });

        Schema::create('purchase_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('product_name_en');
            $table->string('product_name_ur')->nullable();
            $table->string('image_path')->nullable();
            $table->string('required_qty')->nullable();
            $table->string('purchase_qty')->nullable();
            $table->string('previous_price')->nullable();
            $table->string('current_rate')->nullable();
            $table->string('landing_cost')->nullable();
            $table->string('cost_price')->nullable();
            $table->string('sale_price')->nullable();
            $table->string('line_total')->nullable();
            $table->timestamps();

            $table->index(['purchase_id', 'sort_order']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};
