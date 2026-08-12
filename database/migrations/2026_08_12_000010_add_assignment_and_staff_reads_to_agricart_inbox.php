<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agricart_conversations')
            && ! Schema::hasColumn('agricart_conversations', 'assigned_to_user_id')) {
            Schema::table('agricart_conversations', function (Blueprint $table): void {
                $table->foreignId('assigned_to_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('agricart_conversation_reads')) {
            Schema::create('agricart_conversation_reads', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('agricart_conversation_id')->constrained('agricart_conversations')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('last_read_at');
                $table->timestamps();

                $table->unique(['agricart_conversation_id', 'user_id'], 'agricart_conversation_reads_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agricart_conversation_reads');

        Schema::table('agricart_conversations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assigned_to_user_id');
        });
    }
};
