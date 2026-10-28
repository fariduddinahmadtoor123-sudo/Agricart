<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fx_learn_api_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->default('finnhub');
            $table->string('label')->nullable();
            $table->text('api_key');
            $table->string('key_hint', 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('connection_status', 32)->default('untested');
            $table->text('last_test_message')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fx_learn_api_credentials');
    }
};
