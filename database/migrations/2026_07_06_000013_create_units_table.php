<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->string('name_en');
            $table->string('abbreviation_en', 32);
            $table->string('name_ur');
            $table->string('abbreviation_ur', 32);
            $table->string('ai_status')->default('none');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
