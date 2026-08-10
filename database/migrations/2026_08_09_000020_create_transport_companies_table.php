<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_companies', function (Blueprint $table): void {
            $table->id();
            $table->string('transport_code')->unique();
            $table->string('transport_type')->nullable();
            $table->string('status')->default('active');
            $table->string('business_name_en')->nullable();
            $table->string('business_name_ur')->nullable();
            $table->string('country_code', 8)->default('PK');
            $table->string('country_ur')->nullable();
            $table->string('city_key', 64)->nullable();
            $table->string('city_en')->nullable();
            $table->string('city_ur')->nullable();
            $table->string('address_en', 2000)->nullable();
            $table->string('address_ur', 2000)->nullable();
            $table->string('destination_business_name_en')->nullable();
            $table->string('destination_business_name_ur')->nullable();
            $table->string('destination_country_code', 8)->nullable();
            $table->string('destination_country_ur')->nullable();
            $table->string('destination_city_key', 64)->nullable();
            $table->string('destination_city_en')->nullable();
            $table->string('destination_city_ur')->nullable();
            $table->string('destination_address_en', 2000)->nullable();
            $table->string('destination_address_ur', 2000)->nullable();
            $table->json('routes')->nullable();
            $table->json('branches')->nullable();
            $table->json('destination_branches')->nullable();
            $table->json('contacts')->nullable();
            $table->json('bank_accounts')->nullable();
            $table->decimal('credit_limit', 14, 2)->nullable();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->string('profile_photo_path')->nullable();
            $table->string('card_front_path')->nullable();
            $table->string('card_back_path')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('transport_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_companies');
    }
};
