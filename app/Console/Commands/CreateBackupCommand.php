<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

class CreateBackupCommand extends Command
{
    protected $signature = 'backup:create {--type=database : database or full}';

    protected $description = 'Create an idempotent deployment safety backup archive';

    public function handle(BackupService $backups): int
    {
        $type = (string) $this->option('type');

        if (! in_array($type, [BackupService::TYPE_DATABASE, BackupService::TYPE_FULL], true)) {
            $this->components->error('Type must be database or full.');

            return self::FAILURE;
        }

        $entry = $backups->create($type);
        $this->components->info("Created {$entry->filename} ({$entry->formattedSize()}).");

        return self::SUCCESS;
    }
}
