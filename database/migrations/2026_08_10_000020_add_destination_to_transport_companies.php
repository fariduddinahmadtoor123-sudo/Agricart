<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transport_companies')) {
            return;
        }

        Schema::table('transport_companies', function (Blueprint $table): void {
            if (! Schema::hasColumn('transport_companies', 'destination_business_name_en')) {
                $table->string('destination_business_name_en')->nullable()->after('address_ur');
            }

            if (! Schema::hasColumn('transport_companies', 'destination_business_name_ur')) {
                $table->string('destination_business_name_ur')->nullable()->after('destination_business_name_en');
            }

            if (! Schema::hasColumn('transport_companies', 'destination_country_code')) {
                $table->string('destination_country_code', 8)->nullable()->after('destination_business_name_ur');
            }

            if (! Schema::hasColumn('transport_companies', 'destination_country_ur')) {
                $table->string('destination_country_ur')->nullable()->after('destination_country_code');
            }

            if (! Schema::hasColumn('transport_companies', 'destination_city_key')) {
                $table->string('destination_city_key', 64)->nullable()->after('destination_country_ur');
            }

            if (! Schema::hasColumn('transport_companies', 'destination_city_en')) {
                $table->string('destination_city_en')->nullable()->after('destination_city_key');
            }

            if (! Schema::hasColumn('transport_companies', 'destination_city_ur')) {
                $table->string('destination_city_ur')->nullable()->after('destination_city_en');
            }

            if (! Schema::hasColumn('transport_companies', 'destination_address_en')) {
                $table->string('destination_address_en', 2000)->nullable()->after('destination_city_ur');
            }

            if (! Schema::hasColumn('transport_companies', 'destination_address_ur')) {
                $table->string('destination_address_ur', 2000)->nullable()->after('destination_address_en');
            }

            if (! Schema::hasColumn('transport_companies', 'destination_branches')) {
                $table->json('destination_branches')->nullable()->after('branches');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transport_companies')) {
            return;
        }

        Schema::table('transport_companies', function (Blueprint $table): void {
            foreach ([
                'destination_business_name_en',
                'destination_business_name_ur',
                'destination_country_code',
                'destination_country_ur',
                'destination_city_key',
                'destination_city_en',
                'destination_city_ur',
                'destination_address_en',
                'destination_address_ur',
                'destination_branches',
            ] as $column) {
                if (Schema::hasColumn('transport_companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
