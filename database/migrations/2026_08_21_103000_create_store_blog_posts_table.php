<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title_en');
            $table->string('title_ur')->nullable();
            $table->string('slug')->unique();
            $table->string('excerpt_en', 500)->nullable();
            $table->string('excerpt_ur', 500)->nullable();
            $table->text('body_en')->nullable();
            $table->text('body_ur')->nullable();
            $table->string('type', 16)->default('article'); // article|video
            $table->string('youtube_url', 500)->nullable();
            $table->string('cover_image_path')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'published_at']);
            $table->index(['type', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_blog_posts');
    }
};
