<?php

namespace App\Console\Commands;

use App\Services\Media\MediaLibraryService;
use Illuminate\Console\Command;

class RepairMediaLifecycleCommand extends Command
{
    protected $signature = 'media:repair-lifecycle
                            {--dry-run : Report zero-usage assets without deleting them}';

    protected $description = 'Retry failed media synchronization and safely sweep non-library zero-usage assets';

    public function handle(MediaLibraryService $library): int
    {
        $repaired = $library->repairFailures();
        $swept = $library->sweepZeroUsageAssets((bool) $this->option('dry-run'));

        $this->components->info(
            "Repaired {$repaired} synchronization failure(s); ".
            ($this->option('dry-run') ? 'found' : 'removed').
            " {$swept} safe zero-usage asset(s).",
        );

        return self::SUCCESS;
    }
}
