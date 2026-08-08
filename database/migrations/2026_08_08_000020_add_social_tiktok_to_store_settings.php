<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('store_settings', 'social_tiktok')) {
                $table->string('social_tiktok')->nullable()->after('social_twitter');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('store_settings', 'social_tiktok')) {
                $table->dropColumn('social_tiktok');
            }
        });
    }
};
