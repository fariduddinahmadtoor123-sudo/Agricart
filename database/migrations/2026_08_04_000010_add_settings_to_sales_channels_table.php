<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_channels', function (Blueprint $table): void {
            $table->json('settings')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('sales_channels', function (Blueprint $table): void {
            $table->dropColumn('settings');
        });
    }
};
