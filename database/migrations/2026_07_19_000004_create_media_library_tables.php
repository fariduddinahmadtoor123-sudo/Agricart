<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('disk', 64)->default('public');
            $table->string('path', 500);
            $table->string('visibility', 16)->default('public');
            $table->string('original_name')->nullable();
            $table->string('display_name');
            $table->string('mime_type', 120)->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('byte_size')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->char('checksum', 64)->nullable()->index();
            $table->string('source_module', 64)->default('library')->index();
            $table->string('optimization_status', 24)->default('ready');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamp('trashed_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['disk', 'path'], 'media_assets_disk_path_unique');
            $table->index(['source_module', 'created_at'], 'media_assets_source_created');
        });

        Schema::create('media_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_asset_id')->constrained('media_assets')->cascadeOnDelete();
            $table->string('usable_type');
            $table->unsignedBigInteger('usable_id');
            $table->string('collection', 64)->default('main');
            $table->unsignedInteger('position')->default(0);
            $table->string('label')->nullable();
            $table->timestamps();

            $table->index(['usable_type', 'usable_id'], 'media_usages_usable_index');
            $table->unique(
                ['media_asset_id', 'usable_type', 'usable_id', 'collection', 'position'],
                'media_usages_owner_position_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_usages');
        Schema::dropIfExists('media_assets');
    }
};
