<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_transfers', function (Blueprint $table): void {
            $table->string('bank_receipt_path')->nullable()->after('notes');
        });

        Schema::table('expense_payments', function (Blueprint $table): void {
            $table->string('bank_receipt_path')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('fund_transfers', function (Blueprint $table): void {
            $table->dropColumn('bank_receipt_path');
        });

        Schema::table('expense_payments', function (Blueprint $table): void {
            $table->dropColumn('bank_receipt_path');
        });
    }
};
