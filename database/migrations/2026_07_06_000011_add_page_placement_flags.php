<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_pages', function (Blueprint $table): void {
            $table->boolean('show_in_quick_links')->default(false)->after('show_in_footer');
            $table->boolean('show_in_registration')->default(false)->after('show_in_quick_links');
        });
    }

    public function down(): void
    {
        Schema::table('store_pages', function (Blueprint $table): void {
            $table->dropColumn(['show_in_quick_links', 'show_in_registration']);
        });
    }
};
