<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_transfers', function (Blueprint $table) {
            $table->string('handled_by_name', 120)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('fund_transfers', function (Blueprint $table) {
            $table->dropColumn('handled_by_name');
        });
    }
};
