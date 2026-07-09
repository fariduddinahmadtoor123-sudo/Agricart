<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fund_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 32)->unique();
            $table->string('transaction_type', 32);
            $table->string('source_wallet', 16)->nullable();
            $table->foreignId('source_bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('destination_bank_account_id')->nullable()->constrained('bank_accounts', 'id', 'ft_dest_bank_fk')->nullOnDelete();
            $table->foreignId('destination_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('destination_purchaser_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('transaction_date');
            $table->time('transaction_time');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('transaction_date');
            $table->index('transaction_type');
        });

        Schema::create('cash_book_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fund_transfer_id')->nullable()->constrained('fund_transfers')->nullOnDelete();
            $table->string('direction', 8);
            $table->decimal('amount', 14, 2);
            $table->date('transaction_date');
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['transaction_date', 'direction']);
        });

        Schema::table('bank_account_transactions', function (Blueprint $table) {
            $table->foreignId('fund_transfer_id')->nullable()->after('online_sale_order_id')->constrained('fund_transfers', 'id', 'bat_fund_transfer_fk')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bank_account_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fund_transfer_id');
        });

        Schema::dropIfExists('cash_book_transactions');
        Schema::dropIfExists('fund_transfers');
    }
};
