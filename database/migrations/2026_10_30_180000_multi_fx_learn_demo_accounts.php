<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fx_learn_demo_accounts', function (Blueprint $table): void {
            $table->string('account_no', 32)->nullable()->after('id');
            $table->boolean('is_active')->default(true)->after('user_id');
        });

        Schema::table('fx_learn_demo_accounts', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });

        Schema::table('fx_learn_demo_accounts', function (Blueprint $table): void {
            $table->dropUnique(['user_id']);
        });

        Schema::table('fx_learn_demo_accounts', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('user_id');
        });

        $accounts = DB::table('fx_learn_demo_accounts')->orderBy('id')->get(['id', 'user_id']);
        foreach ($accounts as $row) {
            DB::table('fx_learn_demo_accounts')->where('id', $row->id)->update([
                'account_no' => 'FXA-'.str_pad((string) $row->id, 6, '0', STR_PAD_LEFT),
                'is_active' => true,
            ]);
        }

        foreach ($accounts->groupBy('user_id') as $userAccounts) {
            $keepId = (int) $userAccounts->sortByDesc('id')->first()->id;
            DB::table('fx_learn_demo_accounts')
                ->where('user_id', $userAccounts->first()->user_id)
                ->where('id', '!=', $keepId)
                ->update(['is_active' => false]);
        }

        Schema::table('fx_learn_demo_accounts', function (Blueprint $table): void {
            $table->unique('account_no');
        });

        Schema::table('fx_learn_demo_trades', function (Blueprint $table): void {
            $table->foreignId('demo_account_id')
                ->nullable()
                ->after('user_id')
                ->constrained('fx_learn_demo_accounts')
                ->nullOnDelete();
            $table->index(['demo_account_id', 'status']);
        });

        foreach (DB::table('fx_learn_demo_trades')->select('id', 'user_id')->get() as $trade) {
            $accountId = DB::table('fx_learn_demo_accounts')
                ->where('user_id', $trade->user_id)
                ->orderByDesc('is_active')
                ->orderByDesc('id')
                ->value('id');

            if ($accountId) {
                DB::table('fx_learn_demo_trades')->where('id', $trade->id)->update([
                    'demo_account_id' => $accountId,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('fx_learn_demo_trades', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('demo_account_id');
        });

        Schema::table('fx_learn_demo_accounts', function (Blueprint $table): void {
            $table->dropUnique(['account_no']);
            $table->dropColumn(['account_no', 'is_active']);
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('fx_learn_demo_accounts', function (Blueprint $table): void {
            $table->unique('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
