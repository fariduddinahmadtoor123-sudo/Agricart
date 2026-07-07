<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->string('supplier_code')->unique();
            $table->string('supplier_type')->nullable();
            $table->string('status')->default('active');
            $table->string('business_name_en');
            $table->string('business_name_ur')->nullable();
            $table->string('country_code', 8)->default('PK');
            $table->string('country_ur')->nullable();
            $table->string('city_key')->nullable();
            $table->string('city_en')->nullable();
            $table->string('city_ur')->nullable();
            $table->text('address_en');
            $table->text('address_ur')->nullable();
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->json('contacts')->nullable();
            $table->json('bank_accounts')->nullable();
            $table->boolean('portal_enabled')->default(false);
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->string('card_front_path')->nullable();
            $table->string('card_back_path')->nullable();
            $table->string('ai_status')->default('none');
            $table->timestamps();

            $table->index('status');
            $table->index('city_en');
            $table->index('business_name_en');
        });

        Schema::create('category_supplier', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['supplier_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_supplier');
        Schema::dropIfExists('suppliers');
    }
};
