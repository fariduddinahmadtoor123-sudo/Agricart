<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('store_settings')
            ->whereNull('register_url')
            ->orWhere('register_url', '')
            ->update(['register_url' => '/register']);
    }

    public function down(): void
    {
        DB::table('store_settings')
            ->where('register_url', '/register')
            ->update(['register_url' => null]);
    }
};
