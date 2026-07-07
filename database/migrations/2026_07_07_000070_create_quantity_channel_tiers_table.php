<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quantity_channel_tiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quantity_control_id')->constrained('quantity_controls')->cascadeOnDelete();
            $table->string('tier_key', 64);
            $table->string('name_en');
            $table->string('name_ur')->nullable();
            $table->unsignedInteger('min_quantity')->default(1);
            $table->string('price_field', 64);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['quantity_control_id', 'tier_key']);
            $table->unique(['quantity_control_id', 'price_field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quantity_channel_tiers');
    }
};
