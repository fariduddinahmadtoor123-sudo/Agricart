<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clean-slate removal of the experimental FxLearn module.
 * Safe on hosts that already ran the old create migrations (files removed from repo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('fx_learn_demo_trades');
        Schema::dropIfExists('fx_learn_demo_accounts');
        Schema::dropIfExists('fx_learn_api_credentials');

        $permissionIds = DB::table('permissions')
            ->where('group_key', 'fx-learn')
            ->orWhere('key', 'like', 'fx-learn.%')
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        // Remove enum-backed AI log rows that would break after AiLogType cleanup.
        if (Schema::hasTable('ai_logs')) {
            DB::table('ai_logs')->where('type', 'economic_news_bias')->delete();
        }

        // Remove history for create migrations deleted from the repository.
        DB::table('migrations')
            ->whereIn('migration', [
                '2026_10_25_224500_create_fx_learn_demo_terminal_tables',
                '2026_10_27_090000_create_fx_learn_api_credentials_table',
            ])
            ->delete();
    }

    public function down(): void
    {
        // Irreversible clean-slate wipe — recreate via a new FxLearn architecture later.
    }
};
