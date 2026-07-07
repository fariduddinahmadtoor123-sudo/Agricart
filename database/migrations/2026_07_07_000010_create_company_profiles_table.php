<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('business_name_en')->nullable();
            $table->string('business_name_ur')->nullable();
            $table->string('legal_name_en')->nullable();
            $table->string('legal_name_ur')->nullable();
            $table->string('tagline_en')->nullable();
            $table->string('tagline_ur')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('ntn')->nullable();
            $table->string('strn')->nullable();
            $table->string('currency_code', 3)->default('PKR');
            $table->boolean('price_use_decimals')->default(false);
            $table->unsignedTinyInteger('price_decimal_places')->default(2);
            $table->json('phones')->nullable();
            $table->json('addresses')->nullable();
            $table->text('invoice_footer_en')->nullable();
            $table->text('invoice_footer_ur')->nullable();
            $table->string('ai_status')->default('none');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profiles');
    }
};
