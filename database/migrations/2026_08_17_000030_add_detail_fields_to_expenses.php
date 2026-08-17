<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->string('detail_1', 120)->nullable()->after('due_day');
            $table->string('detail_2', 120)->nullable()->after('detail_1');
            $table->string('detail_3', 120)->nullable()->after('detail_2');
            $table->string('detail_4', 120)->nullable()->after('detail_3');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropColumn(['detail_1', 'detail_2', 'detail_3', 'detail_4']);
        });
    }
};
