<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->text('api_key');
            $table->string('model_id');
            $table->string('model_name')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('connection_status')->default('untested');
            $table->text('last_test_message')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_configurations');
    }
};
