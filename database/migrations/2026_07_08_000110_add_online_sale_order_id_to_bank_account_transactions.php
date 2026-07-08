<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_account_transactions', function (Blueprint $table) {
            $table->foreignId('online_sale_order_id')
                ->nullable()
                ->after('sale_id')
                ->constrained('online_sale_orders')
                ->nullOnDelete();

            $table->index('online_sale_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('bank_account_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('online_sale_order_id');
        });
    }
};
