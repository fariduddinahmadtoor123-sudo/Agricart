<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agricart_conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject', 200);
            $table->string('topic', 40)->default('general');
            $table->string('status', 20)->default('open');
            $table->string('guest_name', 120)->nullable();
            $table->string('guest_phone', 30)->nullable();
            $table->string('guest_email', 255)->nullable();
            $table->foreignId('online_sale_order_id')->nullable()->constrained('online_sale_orders')->nullOnDelete();
            $table->foreignId('product_review_id')->nullable()->constrained('product_reviews')->nullOnDelete();
            $table->foreignId('store_inquiry_id')->nullable()->constrained('store_inquiries')->nullOnDelete();
            $table->string('source_url', 2048)->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('customer_last_read_at')->nullable();
            $table->timestamp('admin_last_read_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status', 'last_message_at'], 'agricart_conversations_customer_status_idx');
            $table->index(['status', 'last_message_at'], 'agricart_conversations_status_last_idx');
            $table->index(['topic', 'status'], 'agricart_conversations_topic_status_idx');
        });

        Schema::create('agricart_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('agricart_conversation_id')->constrained('agricart_conversations')->cascadeOnDelete();
            $table->string('sender_type', 20);
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_system')->default(false);
            $table->string('event_key', 80)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['agricart_conversation_id', 'id'], 'agricart_messages_conversation_id_idx');
            $table->index(['sender_type', 'created_at'], 'agricart_messages_sender_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agricart_messages');
        Schema::dropIfExists('agricart_conversations');
    }
};
