<?php

namespace App\Console\Commands;

use App\Models\AiLog;
use Illuminate\Console\Command;

class PruneAiLogsCommand extends Command
{
    protected $signature = 'ai-logs:prune {--days= : Retain logs newer than this many days}';

    protected $description = 'Delete AI activity logs older than the retention period';

    public function handle(): int
    {
        $days = max(1, (int) ($this->option('days') ?: config('retention.ai_log_days')));
        $deleted = AiLog::query()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Deleted {$deleted} AI log(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
