<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fx_learn_demo_accounts', function (Blueprint $table): void {
            $table->string('desk_category', 24)->default('forex')->after('selected_symbol');
        });
    }

    public function down(): void
    {
        Schema::table('fx_learn_demo_accounts', function (Blueprint $table): void {
            $table->dropColumn('desk_category');
        });
    }
};
