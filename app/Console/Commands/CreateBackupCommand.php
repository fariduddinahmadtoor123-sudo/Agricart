<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateBackupCommand extends Command
{
    protected $signature = 'backup:create
        {--type=database : database or full}
        {--source=manual : manual, scheduled, deploy, or safety}';

    protected $description = 'Create a database or full backup archive (used by deploy and the daily scheduler)';

    public function handle(BackupService $backups): int
    {
        $type = (string) $this->option('type');
        $source = (string) $this->option('source');

        if (! in_array($type, [BackupService::TYPE_DATABASE, BackupService::TYPE_FULL], true)) {
            $this->components->error('Type must be database or full.');

            return self::FAILURE;
        }

        if (! in_array($source, ['manual', 'scheduled', 'deploy', 'safety'], true)) {
            $source = 'manual';
        }

        try {
            $entry = $backups->create($type, ['source' => $source]);
            $this->components->info("Created {$entry->filename} ({$entry->formattedSize()}).");
            Log::info('backup.created', [
                'type' => $type,
                'source' => $source,
                'filename' => $entry->filename,
                'bytes' => $entry->sizeBytes,
            ]);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('backup.create_failed', [
                'type' => $type,
                'source' => $source,
                'message' => $exception->getMessage(),
            ]);
            report($exception);
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
