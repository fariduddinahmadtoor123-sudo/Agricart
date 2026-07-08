<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 32)->nullable()->unique();
            $table->string('name_en');
            $table->string('name_ur')->nullable();
            $table->string('bank_key', 64)->nullable();
            $table->string('bank_name_en')->nullable();
            $table->string('bank_name_ur')->nullable();
            $table->string('account_title')->nullable();
            $table->string('account_number', 64)->nullable();
            $table->string('iban', 64)->nullable();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->string('currency', 8)->default('PKR');
            $table->string('status', 16)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('bank_account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('direction', 8);
            $table->decimal('amount', 14, 2);
            $table->date('transaction_date');
            $table->foreignId('counterparty_bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->uuid('transfer_group')->nullable();
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['bank_account_id', 'transaction_date']);
            $table->index('sale_id');
            $table->index('transfer_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_account_transactions');
        Schema::dropIfExists('bank_accounts');
    }
};
