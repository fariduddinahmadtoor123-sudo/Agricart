<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('store_settings')
            ->where('login_url', '/admin/login')
            ->update(['login_url' => '/login']);
    }

    public function down(): void
    {
        DB::table('store_settings')
            ->where('login_url', '/login')
            ->update(['login_url' => '/admin/login']);
    }
};
