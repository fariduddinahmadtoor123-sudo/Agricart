<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table): void {
            $table->string('dispute_status', 32)->default('ok')->after('payment_status');
            $table->index('dispute_status');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table): void {
            $table->dropIndex(['dispute_status']);
            $table->dropColumn('dispute_status');
        });
    }
};
