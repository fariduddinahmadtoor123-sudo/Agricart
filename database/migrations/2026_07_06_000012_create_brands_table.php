<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->string('name_en');
            $table->string('name_ur');
            $table->string('image_path');
            $table->text('description_en')->nullable();
            $table->text('description_ur')->nullable();
            $table->string('website_url')->nullable();
            $table->string('ai_status')->default('none');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
