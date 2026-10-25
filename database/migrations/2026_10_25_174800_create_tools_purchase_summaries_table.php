<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tools_purchase_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('summary_no', 32)->index();
            $table->string('summary_date', 32)->nullable();
            $table->string('lang', 8)->default('en');
            $table->string('shop_name')->nullable();
            $table->text('shop_address')->nullable();
            $table->json('shop_phones')->nullable();
            $table->string('prepared_by')->nullable();
            $table->json('cells')->nullable();
            $table->json('payment_sources')->nullable();
            $table->json('pending_bills')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();

            $table->unique(['user_id', 'summary_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tools_purchase_summaries');
    }
};
