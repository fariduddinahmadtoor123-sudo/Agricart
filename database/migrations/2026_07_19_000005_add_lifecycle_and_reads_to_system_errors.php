<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_errors', function (Blueprint $table): void {
            $table->string('resolution_type', 30)->nullable()->after('resolved_by_id');
            $table->text('resolution_note')->nullable()->after('resolution_type');
            $table->timestamp('verified_at')->nullable()->after('resolution_note');
        });

        Schema::create('system_error_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('system_error_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('occurrences_seen')->default(0);
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['system_error_id', 'user_id']);
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_error_reads');

        Schema::table('system_errors', function (Blueprint $table): void {
            $table->dropColumn(['resolution_type', 'resolution_note', 'verified_at']);
        });
    }
};
