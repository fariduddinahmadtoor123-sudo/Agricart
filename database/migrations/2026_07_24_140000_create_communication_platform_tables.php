<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comm_channels', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('name', 120);
            $table->boolean('is_active')->default(true);
            $table->json('allowed_actor_types');
            $table->boolean('allows_groups')->default(false);
            $table->boolean('allows_attachments')->default(true);
            $table->json('allowed_mime_groups')->nullable();
            $table->boolean('allows_tasks')->default(false);
            $table->string('visibility', 40)->default('participants');
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('comm_conversations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('channel_id')->constrained('comm_channels')->cascadeOnDelete();
            $table->string('subject', 200)->nullable();
            $table->string('type', 40)->default('direct');
            $table->string('status', 20)->default('open');
            $table->unsignedBigInteger('created_by_participant_id')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['channel_id', 'status', 'last_message_at'], 'comm_conversations_channel_status_last_idx');
        });

        Schema::create('comm_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('comm_conversations')->cascadeOnDelete();
            $table->string('actor_type', 64);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('role', 40)->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->string('notifications', 20)->default('all');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['actor_type', 'actor_id'], 'comm_participants_actor_idx');
            $table->index(['conversation_id', 'left_at'], 'comm_participants_conversation_left_idx');
            $table->unique(
                ['conversation_id', 'actor_type', 'actor_id'],
                'comm_participants_conversation_actor_unique',
            );
        });

        Schema::table('comm_conversations', function (Blueprint $table): void {
            $table->foreign('created_by_participant_id', 'comm_conversations_created_by_participant_fk')
                ->references('id')
                ->on('comm_participants')
                ->nullOnDelete();
        });

        Schema::create('comm_messages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('conversation_id')->constrained('comm_conversations')->cascadeOnDelete();
            $table->foreignId('participant_id')->nullable()->constrained('comm_participants')->nullOnDelete();
            $table->string('message_type', 40)->default('text');
            $table->text('body')->nullable();
            $table->string('body_format', 20)->default('plain');
            $table->foreignId('parent_id')->nullable()->constrained('comm_messages')->nullOnDelete();
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->foreignId('deleted_by_participant_id')->nullable()->constrained('comm_participants')->nullOnDelete();
            $table->string('event_key', 120)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'id'], 'comm_messages_conversation_id_idx');
            $table->index(['conversation_id', 'created_at'], 'comm_messages_conversation_created_idx');
            $table->unique(['conversation_id', 'event_key'], 'comm_messages_conversation_event_unique');
        });

        Schema::table('comm_conversations', function (Blueprint $table): void {
            $table->foreign('last_message_id', 'comm_conversations_last_message_fk')
                ->references('id')
                ->on('comm_messages')
                ->nullOnDelete();
        });

        Schema::table('comm_participants', function (Blueprint $table): void {
            $table->foreign('last_read_message_id', 'comm_participants_last_read_message_fk')
                ->references('id')
                ->on('comm_messages')
                ->nullOnDelete();
        });

        Schema::create('comm_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained('comm_messages')->cascadeOnDelete();
            $table->string('disk', 40);
            $table->string('path', 500);
            $table->string('original_name', 255);
            $table->string('mime', 120);
            $table->unsignedBigInteger('size');
            $table->string('kind', 40);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['message_id', 'kind'], 'comm_attachments_message_kind_idx');
        });

        Schema::create('comm_conversation_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('comm_conversations')->cascadeOnDelete();
            $table->string('linkable_type', 64);
            $table->unsignedBigInteger('linkable_id');
            $table->string('relation', 40)->default('about');
            $table->foreignId('created_by_participant_id')->nullable()->constrained('comm_participants')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['conversation_id', 'linkable_type', 'linkable_id', 'relation'],
                'comm_conversation_links_unique',
            );
            $table->index(['linkable_type', 'linkable_id'], 'comm_conversation_links_linkable_idx');
        });

        Schema::create('comm_task_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('comm_conversations')->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('comm_messages')->nullOnDelete();
            $table->string('title', 200);
            $table->string('assignable_type', 64)->nullable();
            $table->unsignedBigInteger('assignable_id')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->string('status', 20)->default('open');
            $table->string('completed_by_type', 64)->nullable();
            $table->unsignedBigInteger('completed_by_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'status'], 'comm_task_links_conversation_status_idx');
            $table->index(['assignable_type', 'assignable_id'], 'comm_task_links_assignable_idx');
        });

        Schema::create('comm_delivery_outbox', function (Blueprint $table): void {
            $table->id();
            $table->string('channel', 40);
            $table->string('driver', 40);
            $table->foreignId('message_id')->nullable()->constrained('comm_messages')->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('comm_conversations')->nullOnDelete();
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'available_at'], 'comm_delivery_outbox_status_available_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comm_delivery_outbox');
        Schema::dropIfExists('comm_task_links');
        Schema::dropIfExists('comm_conversation_links');
        Schema::dropIfExists('comm_attachments');

        Schema::table('comm_participants', function (Blueprint $table): void {
            $table->dropForeign('comm_participants_last_read_message_fk');
        });

        Schema::table('comm_conversations', function (Blueprint $table): void {
            $table->dropForeign('comm_conversations_last_message_fk');
            $table->dropForeign('comm_conversations_created_by_participant_fk');
        });

        Schema::dropIfExists('comm_messages');
        Schema::dropIfExists('comm_participants');
        Schema::dropIfExists('comm_conversations');
        Schema::dropIfExists('comm_channels');
    }
};
