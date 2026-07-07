<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reorder_queue_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('batch_number')->unique();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('reorder_queue_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reorder_queue_batch_id')->constrained('reorder_queue_batches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('required_qty')->nullable();
            $table->string('previous_price')->nullable();
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('send_count')->default(1);
            $table->timestamp('queued_at');
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['status', 'queued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reorder_queue_items');
        Schema::dropIfExists('reorder_queue_batches');
    }
};
