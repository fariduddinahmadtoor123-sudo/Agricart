<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_message_threads', function (Blueprint $table): void {
            $table->id();
            $table->string('subject');
            $table->string('context_type', 64)->nullable();
            $table->unsignedBigInteger('context_id')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['context_type', 'context_id'], 'smt_context_idx');
            $table->index('last_message_at', 'smt_last_message_idx');
        });

        Schema::create('staff_message_thread_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('thread_id')
                ->constrained('staff_message_threads', 'id', 'smtp_thread_fk')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users', 'id', 'smtp_user_fk')
                ->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['thread_id', 'user_id'], 'smtp_thread_user_unq');
            $table->index(['user_id', 'last_read_at'], 'smtp_user_read_idx');
        });

        Schema::create('staff_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('thread_id')
                ->constrained('staff_message_threads', 'id', 'sm_thread_fk')
                ->cascadeOnDelete();
            $table->foreignId('sender_id')
                ->constrained('users', 'id', 'sm_sender_fk')
                ->cascadeOnDelete();
            $table->text('body');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['thread_id', 'created_at'], 'sm_thread_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_messages');
        Schema::dropIfExists('staff_message_thread_participants');
        Schema::dropIfExists('staff_message_threads');
    }
};
