<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class PruneActivityLogsCommand extends Command
{
    protected $signature = 'activity-logs:prune {--days= : Retain logs newer than this many days}';

    protected $description = 'Delete user activity logs older than the retention period';

    public function handle(): int
    {
        $days = max(1, (int) ($this->option('days') ?: config('retention.activity_log_days')));
        $deleted = ActivityLog::query()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Deleted {$deleted} activity log(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
