<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_planning_sheets', function (Blueprint $table): void {
            $table->string('locale', 8)->default('en')->after('paper_size');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_planning_sheets', function (Blueprint $table): void {
            $table->dropColumn('locale');
        });
    }
};
