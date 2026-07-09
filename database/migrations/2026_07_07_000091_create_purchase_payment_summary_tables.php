<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_payment_summaries', function (Blueprint $table): void {
            $table->id();
            $table->string('summary_number')->unique();
            $table->date('summary_date');
            $table->string('locale', 8)->default('en');
            $table->string('paper_size', 16)->default('a4');
            $table->text('notes')->nullable();
            $table->json('payment_sources')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('summary_date');
            $table->index('created_at');
        });

        Schema::create('purchase_payment_summary_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_payment_summary_id')->constrained('purchase_payment_summaries', 'id', 'pps_items_summary_fk')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_manual')->default(false);
            $table->string('name_en');
            $table->string('name_ur')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('payment_amount')->nullable();
            $table->string('line_status', 16)->nullable();
            $table->timestamps();

            $table->index(['purchase_payment_summary_id', 'sort_order'], 'pps_items_summary_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payment_summary_items');
        Schema::dropIfExists('purchase_payment_summaries');
    }
};
