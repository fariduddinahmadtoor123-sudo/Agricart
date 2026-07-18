<?php

namespace App\Console\Commands;

use App\Models\SystemError;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PruneSystemErrorsCommand extends Command
{
    protected $signature = 'system-errors:prune {--days=90 : Delete resolved errors older than this many days}';

    protected $description = 'Delete resolved system errors after the configured retention period';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));

        if (! Schema::hasTable('system_errors')) {
            $this->components->info('System errors table does not exist yet.');

            return self::SUCCESS;
        }

        $deleted = SystemError::query()
            ->where('status', SystemError::STATUS_RESOLVED)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<', now()->subDays($days))
            ->delete();

        $this->components->info("Deleted {$deleted} resolved system error record(s).");

        return self::SUCCESS;
    }
}
