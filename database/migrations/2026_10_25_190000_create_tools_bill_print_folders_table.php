<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tools_bill_print_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('folder_name', 120);
            $table->string('folder_date', 32)->nullable();
            $table->json('images')->nullable();
            $table->unsignedSmallInteger('image_count')->default(0);
            $table->string('status', 32)->default('draft');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'folder_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tools_bill_print_folders');
    }
};
