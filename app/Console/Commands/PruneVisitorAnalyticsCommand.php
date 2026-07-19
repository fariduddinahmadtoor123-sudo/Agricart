<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneVisitorAnalyticsCommand extends Command
{
    protected $signature = 'analytics:prune
        {--raw-days= : Raw page-view and session retention}
        {--visitor-days= : Anonymous visitor retention}
        {--rollup-days= : Aggregated rollup retention}';

    protected $description = 'Prune expired raw visitor analytics records in bounded batches';

    public function handle(): int
    {
        $rawDays = max(7, (int) ($this->option('raw-days') ?: config('retention.analytics_raw_days')));
        $visitorDays = max($rawDays, (int) ($this->option('visitor-days') ?: config('retention.analytics_visitor_days')));
        $rollupDays = max($rawDays, (int) ($this->option('rollup-days') ?: config('retention.analytics_rollup_days')));

        $views = $this->deleteInBatches('analytics_page_views', 'occurred_at', now()->subDays($rawDays));
        $sessions = $this->deleteInBatches('analytics_sessions', 'last_seen_at', now()->subDays($rawDays));
        $visitors = $this->deleteInBatches('analytics_visitors', 'last_seen_at', now()->subDays($visitorDays));
        $rollups = $this->deleteInBatches('analytics_rollups', 'period_start', now()->subDays($rollupDays));

        $this->components->info("Pruned {$views} page views, {$sessions} sessions, {$visitors} visitors, and {$rollups} rollups.");

        return self::SUCCESS;
    }

    private function deleteInBatches(string $table, string $column, mixed $before): int
    {
        $deleted = 0;

        do {
            $ids = DB::table($table)
                ->where($column, '<', $before)
                ->orderBy('id')
                ->limit(5000)
                ->pluck('id');

            $count = $ids->isEmpty()
                ? 0
                : DB::table($table)->whereIn('id', $ids)->delete();
            $deleted += $count;
        } while ($count === 5000);

        return $deleted;
    }
}
