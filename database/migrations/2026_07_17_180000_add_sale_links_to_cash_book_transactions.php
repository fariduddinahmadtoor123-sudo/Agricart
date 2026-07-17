<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_book_transactions', function (Blueprint $table): void {
            $table->foreignId('sale_id')->nullable()->after('expense_payment_id')->constrained('sales')->nullOnDelete();
            $table->foreignId('sale_return_id')->nullable()->after('sale_id')->constrained('sale_returns')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_book_transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('sale_return_id');
            $table->dropConstrainedForeignId('sale_id');
        });
    }
};
