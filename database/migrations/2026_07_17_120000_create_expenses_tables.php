<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('status')->default('active');
            $table->string('kind', 16)->default('operating');
            $table->string('name_en');
            $table->string('name_ur')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->string('expense_number', 32)->unique();
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->string('status')->default('active');
            $table->string('name_en');
            $table->string('name_ur')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ur')->nullable();
            $table->decimal('amount', 14, 2);
            $table->date('expense_date');
            $table->time('expense_time')->nullable();
            $table->string('source_wallet', 16);
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('payee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payee_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('expense_date');
            $table->index('status');
        });

        Schema::table('cash_book_transactions', function (Blueprint $table): void {
            $table->foreignId('expense_id')->nullable()->after('fund_transfer_id')->constrained('expenses')->nullOnDelete();
        });

        Schema::table('bank_account_transactions', function (Blueprint $table): void {
            $table->foreignId('expense_id')->nullable()->after('fund_transfer_id')->constrained('expenses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bank_account_transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('expense_id');
        });

        Schema::table('cash_book_transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('expense_id');
        });

        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
