<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_book_transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('cash_book_transactions', 'online_sale_order_id')) {
                $table->foreignId('online_sale_order_id')
                    ->nullable()
                    ->after('sale_return_id')
                    ->constrained('online_sale_orders')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('cash_book_transactions', 'source_key')) {
                $table->string('source_key', 191)->nullable()->after('notes');
                $table->unique('source_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cash_book_transactions', function (Blueprint $table): void {
            if (Schema::hasColumn('cash_book_transactions', 'source_key')) {
                $table->dropUnique(['source_key']);
                $table->dropColumn('source_key');
            }

            if (Schema::hasColumn('cash_book_transactions', 'online_sale_order_id')) {
                $table->dropConstrainedForeignId('online_sale_order_id');
            }
        });
    }
};
