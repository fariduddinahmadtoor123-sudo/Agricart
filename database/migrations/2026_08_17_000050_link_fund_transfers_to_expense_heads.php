<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_transfers', function (Blueprint $table): void {
            $table->foreignId('destination_expense_id')->nullable()->after('destination_purchaser_id')->constrained('expenses')->nullOnDelete();
            $table->foreignId('expense_payment_id')->nullable()->after('destination_expense_id')->constrained('expense_payments')->nullOnDelete();
        });

        Schema::table('expense_payments', function (Blueprint $table): void {
            $table->foreignId('fund_transfer_id')->nullable()->after('created_by')->constrained('fund_transfers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expense_payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('fund_transfer_id');
        });

        Schema::table('fund_transfers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('expense_payment_id');
            $table->dropConstrainedForeignId('destination_expense_id');
        });
    }
};
