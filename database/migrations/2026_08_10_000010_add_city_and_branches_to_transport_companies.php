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
            if (! Schema::hasColumn('transport_companies', 'city_key')) {
                $table->string('city_key', 64)->nullable()->after('country_ur');
            }

            if (! Schema::hasColumn('transport_companies', 'city_en')) {
                $table->string('city_en')->nullable()->after('city_key');
            }

            if (! Schema::hasColumn('transport_companies', 'city_ur')) {
                $table->string('city_ur')->nullable()->after('city_en');
            }

            if (! Schema::hasColumn('transport_companies', 'branches')) {
                $table->json('branches')->nullable()->after('routes');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transport_companies')) {
            return;
        }

        Schema::table('transport_companies', function (Blueprint $table): void {
            foreach (['city_key', 'city_en', 'city_ur', 'branches'] as $column) {
                if (Schema::hasColumn('transport_companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
