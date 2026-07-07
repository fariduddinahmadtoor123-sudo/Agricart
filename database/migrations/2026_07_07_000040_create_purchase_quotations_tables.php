<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_quotations', function (Blueprint $table): void {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->foreignId('purchaser_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('locale', 8)->default('en');
            $table->date('quotation_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('quotation_date');
            $table->index(['purchaser_id', 'created_at']);
        });

        Schema::create('purchase_quotation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_quotation_id')->constrained('purchase_quotations')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('product_name_en');
            $table->string('product_name_ur')->nullable();
            $table->string('image_path')->nullable();
            $table->string('required_qty')->nullable();
            $table->string('previous_price')->nullable();
            $table->string('current_price')->nullable();
            $table->timestamps();

            $table->index(['purchase_quotation_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_quotation_items');
        Schema::dropIfExists('purchase_quotations');
    }
};
