<?php

namespace App\Console\Commands;

use App\Services\SystemErrors\SystemErrorLifecycleService;
use Illuminate\Console\Command;

class ReconcileSystemErrorsCommand extends Command
{
    protected $signature = 'system-errors:reconcile';

    protected $description = 'Verify and automatically resolve known transient or environment system errors';

    public function handle(SystemErrorLifecycleService $lifecycle): int
    {
        $resolved = $lifecycle->reconcile();
        $this->components->info("Automatically resolved {$resolved} verified system error(s).");

        return self::SUCCESS;
    }
}
