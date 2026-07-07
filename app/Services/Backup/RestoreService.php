<?php

namespace App\Services\Backup;

use App\Services\Catalog\CatalogCache;
use App\Services\Store\StoreLayoutService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class RestoreService
{
    public function __construct(
        private readonly BackupService $backups,
        private readonly DatabaseImporter $databaseImporter,
    ) {}

    public function restore(string $backupId, bool $createSafetyBackup = true): void
    {
        set_time_limit(0);

        $entry = $this->backups->find($backupId);

        if ($entry === null) {
            throw ValidationException::withMessages([
                'restore' => __('agricart.backups.messages.backup_not_found'),
            ]);
        }

        $zipPath = $this->backups->absolutePath($entry->filename);
        $workDir = storage_path('app/restore-work-' . $backupId);

        File::ensureDirectoryExists($workDir);

        try {
            if ($createSafetyBackup) {
                app(BackupService::class)->create(BackupService::TYPE_DATABASE);
            }

            Artisan::call('down', ['--retry' => 60]);

            $zip = new ZipArchive;

            if ($zip->open($zipPath) !== true) {
                throw ValidationException::withMessages([
                    'restore' => __('agricart.backups.messages.zip_failed'),
                ]);
            }

            if (! $zip->extractTo($workDir)) {
                $zip->close();

                throw ValidationException::withMessages([
                    'restore' => __('agricart.backups.messages.zip_failed'),
                ]);
            }

            $zip->close();

            $databaseFile = $this->resolveDatabaseFile($workDir);

            if ($databaseFile === null) {
                throw ValidationException::withMessages([
                    'restore' => __('agricart.backups.messages.database_missing'),
                ]);
            }

            $this->databaseImporter->importFrom($databaseFile);

            if ($entry->type === BackupService::TYPE_FULL) {
                $this->restorePublicFiles($workDir . DIRECTORY_SEPARATOR . 'files');
            }

            Artisan::call('up');
            Artisan::call('cache:clear');
            CatalogCache::forgetOverviewStats();
            app(StoreLayoutService::class)->clearCache();
        } catch (\Throwable $exception) {
            Artisan::call('up');

            throw $exception;
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    private function resolveDatabaseFile(string $workDir): ?string
    {
        foreach (['database.sql', 'database.sqlite'] as $name) {
            $path = $workDir . DIRECTORY_SEPARATOR . $name;

            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function restorePublicFiles(string $filesDir): void
    {
        if (! is_dir($filesDir)) {
            return;
        }

        $targetRoot = (string) config('backup.public_root', storage_path('app/public'));
        File::ensureDirectoryExists($targetRoot);

        File::copyDirectory($filesDir, $targetRoot);
    }
}
