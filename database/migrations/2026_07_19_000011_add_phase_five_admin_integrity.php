<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_sync_failures', function (Blueprint $table): void {
            $table->id();
            $table->string('owner_type');
            $table->unsignedBigInteger('owner_id');
            $table->string('operation', 16);
            $table->json('changed_fields')->nullable();
            $table->json('old_paths')->nullable();
            $table->text('last_error');
            $table->unsignedInteger('attempts')->default(1);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['resolved_at', 'id']);
            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_sync_failures');
    }
};
