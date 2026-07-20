<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('name_en');
            $table->string('name_ur');
            $table->text('description_en')->nullable();
            $table->text('description_ur')->nullable();
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });

        Schema::create('delivery_method_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_method_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('allowed_payment_methods')->nullable();
            $table->boolean('cod_allowed')->default(true);
            $table->decimal('cod_min_advance_percent', 5, 2)->default(0);
            $table->boolean('full_advance_required')->default(false);
            $table->text('customer_notice_en')->nullable();
            $table->text('customer_notice_ur')->nullable();
            $table->string('shipping_charge_mode')->default('calculate_later');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_method_rules');
        Schema::dropIfExists('delivery_methods');
    }
};
