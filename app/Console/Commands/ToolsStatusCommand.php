<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hostinger / production check: are External Apps rows still in MySQL?
 */
class ToolsStatusCommand extends Command
{
    protected $signature = 'tools:status {--user= : Limit counts to one user_id}';

    protected $description = 'Show External Apps (Tools) table counts by user and status';

    public function handle(): int
    {
        $userFilter = $this->option('user');
        $userId = $userFilter !== null && $userFilter !== '' ? (int) $userFilter : null;

        $this->info('DB: '.config('database.connections.'.config('database.default').'.database'));
        $this->newLine();

        $tables = [
            'tools_reorder_orders' => ['order_no', 'purchaser_name', 'status', 'user_id', 'updated_at'],
            'tools_purchase_summaries' => ['summary_no', 'status', 'user_id', 'updated_at'],
            'tools_pending_payment_sheets' => ['sheet_no', 'status', 'user_id', 'updated_at'],
            'tools_home_expense_sheets' => ['title', 'status', 'user_id', 'updated_at'],
            'tools_barcode_print_folders' => ['status', 'user_id', 'updated_at'],
            'tools_bill_print_folders' => ['folder_name', 'status', 'user_id', 'updated_at'],
        ];

        foreach ($tables as $table => $preferCols) {
            if (! Schema::hasTable($table)) {
                $this->error("{$table}: TABLE MISSING");

                continue;
            }

            $q = DB::table($table);
            if ($userId !== null) {
                $q->where('user_id', $userId);
            }

            $total = (clone $q)->count();
            $this->line("=== {$table} (total {$total}) ===");

            $groups = DB::table($table)
                ->when($userId !== null, fn ($qq) => $qq->where('user_id', $userId))
                ->select('user_id', 'status', DB::raw('count(*) as c'))
                ->groupBy('user_id', 'status')
                ->orderBy('user_id')
                ->get();

            if ($groups->isEmpty()) {
                $this->warn('  (empty)');
            } else {
                foreach ($groups as $g) {
                    $this->line("  user_id={$g->user_id} status={$g->status} count={$g->c}");
                }
            }

            if ($total > 0) {
                $cols = array_values(array_intersect(Schema::getColumnListing($table), $preferCols));
                if ($cols === []) {
                    $cols = array_slice(Schema::getColumnListing($table), 0, 6);
                }
                foreach (DB::table($table)
                    ->when($userId !== null, fn ($qq) => $qq->where('user_id', $userId))
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get($cols) as $row) {
                    $this->line('  '.json_encode($row, JSON_UNESCAPED_UNICODE));
                }
            }

            $this->newLine();
        }

        $this->comment('UI list shows only status saved + closed for the logged-in user_id.');
        $this->comment('If totals > 0 here but admin UI is empty, you are logged in as a different user.');

        return self::SUCCESS;
    }
}
