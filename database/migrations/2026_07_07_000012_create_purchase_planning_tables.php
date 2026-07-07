<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_planning_sheets', function (Blueprint $table): void {
            $table->id();
            $table->string('planning_number')->unique();
            $table->string('paper_size', 16)->default('a4');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('purchase_planning_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_planning_sheet_id')->constrained('purchase_planning_sheets')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('product_name_en');
            $table->string('product_name_ur')->nullable();
            $table->string('image_path')->nullable();
            $table->string('stock_qty')->nullable();
            $table->string('required_qty')->nullable();
            $table->string('low_qty')->nullable();
            $table->string('purchase_qty')->nullable();
            $table->string('landing_price')->nullable();
            $table->string('sale_price')->nullable();
            $table->timestamps();

            $table->index(['purchase_planning_sheet_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_planning_items');
        Schema::dropIfExists('purchase_planning_sheets');
    }
};
