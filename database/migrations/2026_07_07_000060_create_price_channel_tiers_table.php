<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_channel_tiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_control_id')->constrained('price_controls')->cascadeOnDelete();
            $table->string('tier_key', 64);
            $table->string('name_en');
            $table->string('name_ur')->nullable();
            $table->decimal('markup_percent', 8, 3)->default(0);
            $table->string('product_field', 64);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['price_control_id', 'tier_key']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->json('channel_prices')->nullable()->after('distributor_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('channel_prices');
        });

        Schema::dropIfExists('price_channel_tiers');
    }
};
