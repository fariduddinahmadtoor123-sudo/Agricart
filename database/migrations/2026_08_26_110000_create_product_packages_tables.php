<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('package_number', 32)->unique();
            $table->string('slug')->unique();
            $table->string('name_en');
            $table->string('name_ur')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('product_package_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_package_id')->constrained('product_packages')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_package_id', 'product_id']);
        });

        Schema::create('product_package_category', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_package_id')->constrained('product_packages')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_package_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_package_category');
        Schema::dropIfExists('product_package_items');
        Schema::dropIfExists('product_packages');
    }
};
