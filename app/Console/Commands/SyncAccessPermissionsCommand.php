<?php

namespace App\Console\Commands;

use App\Services\Access\PermissionRegistry;
use Illuminate\Console\Command;

class SyncAccessPermissionsCommand extends Command
{
    protected $signature = 'access:sync-permissions';

    protected $description = 'Sync PermissionRegistry keys into the database (roles UI + Super Admin pivots)';

    public function handle(): int
    {
        $ids = PermissionRegistry::syncToDatabase();

        $this->components->info('Synced '.count($ids).' permission(s) from the registry.');

        return self::SUCCESS;
    }
}
