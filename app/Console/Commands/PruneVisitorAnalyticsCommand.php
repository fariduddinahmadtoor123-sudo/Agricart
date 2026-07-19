<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneVisitorAnalyticsCommand extends Command
{
    protected $signature = 'analytics:prune
        {--raw-days= : Raw page-view and session retention}
        {--visitor-days= : Anonymous visitor retention}';

    protected $description = 'Prune expired raw visitor analytics records in bounded batches';

    public function handle(): int
    {
        $rawDays = max(7, (int) ($this->option('raw-days') ?: config('analytics.raw_retention_days')));
        $visitorDays = max($rawDays, (int) ($this->option('visitor-days') ?: config('analytics.visitor_retention_days')));

        $views = $this->deleteInBatches('analytics_page_views', 'occurred_at', now()->subDays($rawDays));
        $sessions = $this->deleteInBatches('analytics_sessions', 'last_seen_at', now()->subDays($rawDays));
        $visitors = $this->deleteInBatches('analytics_visitors', 'last_seen_at', now()->subDays($visitorDays));

        $this->components->info("Pruned {$views} page views, {$sessions} sessions, and {$visitors} visitors.");

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
