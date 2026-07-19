<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('bank_accounts')
            ->where('opening_balance', 0)
            ->orderBy('id')
            ->eachById(function (object $account): void {
                $opening = (float) DB::table('bank_account_transactions')
                    ->where('bank_account_id', $account->id)
                    ->where('type', 'opening')
                    ->where('direction', 'credit')
                    ->sum('amount');

                if ($opening > 0) {
                    DB::table('bank_accounts')->where('id', $account->id)->update([
                        'opening_balance' => round($opening, 2),
                    ]);
                }
            });

        Schema::create('cash_wallet_balances', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->decimal('balance', 14, 2)->default(0);
            $table->timestamps();
        });

        $cashIn = (float) DB::table('cash_book_transactions')->where('direction', 'in')->sum('amount');
        $cashOut = (float) DB::table('cash_book_transactions')->where('direction', 'out')->sum('amount');

        DB::table('cash_wallet_balances')->insert([
            'id' => 1,
            'balance' => round($cashIn - $cashOut, 2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('bank_account_transactions', function (Blueprint $table): void {
            $table->string('source_key', 191)->nullable()->after('transfer_group');
            $table->unique('source_key', 'bat_source_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bank_account_transactions', function (Blueprint $table): void {
            $table->dropUnique('bat_source_key_unique');
            $table->dropColumn('source_key');
        });

        Schema::dropIfExists('cash_wallet_balances');
    }
};
