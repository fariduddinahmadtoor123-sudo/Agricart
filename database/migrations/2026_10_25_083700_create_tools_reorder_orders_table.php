<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tools_reorder_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_no', 32)->index();
            $table->string('order_date', 32)->nullable();
            $table->string('lang', 8)->default('en');
            $table->string('shop_name')->nullable();
            $table->text('shop_address')->nullable();
            $table->json('shop_phones')->nullable();
            $table->string('purchaser_name')->nullable();
            $table->unsignedInteger('purchaser_index')->nullable();
            $table->string('prepared_by')->nullable();
            $table->json('cells')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();

            $table->unique(['user_id', 'order_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tools_reorder_orders');
    }
};
