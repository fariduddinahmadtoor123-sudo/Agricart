<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_account_transactions', function (Blueprint $table): void {
            $table->index(
                ['bank_account_id', 'direction', 'type'],
                'bat_acct_direction_type_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('bank_account_transactions', function (Blueprint $table): void {
            $table->dropIndex('bat_acct_direction_type_idx');
        });
    }
};
