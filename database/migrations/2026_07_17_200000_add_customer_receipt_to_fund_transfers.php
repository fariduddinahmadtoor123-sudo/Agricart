<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_transfers', function (Blueprint $table): void {
            $table->foreignId('destination_customer_id')
                ->nullable()
                ->after('destination_supplier_id')
                ->constrained('customers')
                ->nullOnDelete();
        });

        Schema::table('bank_account_transactions', function (Blueprint $table): void {
            $table->foreignId('customer_id')
                ->nullable()
                ->after('supplier_id')
                ->constrained('customers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bank_account_transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('customer_id');
        });

        Schema::table('fund_transfers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('destination_customer_id');
        });
    }
};
