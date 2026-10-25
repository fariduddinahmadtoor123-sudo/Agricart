<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tools_barcode_print_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sticker_name', 160);
            $table->json('stickers')->nullable();
            $table->unsignedSmallInteger('sticker_count')->default(0);
            $table->unsignedInteger('print_count')->default(0);
            $table->string('status', 32)->default('saved');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'sticker_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tools_barcode_print_folders');
    }
};
