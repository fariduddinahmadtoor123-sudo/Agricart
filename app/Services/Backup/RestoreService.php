<?php

namespace App\Services\Backup;

use App\Services\Catalog\CatalogCache;
use App\Services\Store\StoreLayoutService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class RestoreService
{
    public function __construct(
        private readonly BackupService $backups,
        private readonly DatabaseImporter $databaseImporter,
        private readonly LocalLoginPreserver $localLogins,
        private readonly BackupArchive $archive,
    ) {}

    public function restore(string $backupId, bool $createSafetyBackup = true, bool $preserveLocalLogins = true): void
    {
        set_time_limit(0);

        $entry = $this->backups->find($backupId);

        if ($entry === null) {
            throw ValidationException::withMessages([
                'restore' => __('agricart.backups.messages.backup_not_found'),
            ]);
        }

        $zipPath = $this->backups->absolutePath($entry->filename);
        $workDir = storage_path('app/private/restore-work-'.bin2hex(random_bytes(16)));
        $inspection = $this->archive->inspect($zipPath, 'restore');

        File::ensureDirectoryExists($workDir, 0700);

        $shouldPreserve = $preserveLocalLogins && (bool) config('backup.preserve_local_logins', true);

        try {
            if ($shouldPreserve) {
                $this->localLogins->capture();
            }

            if ($createSafetyBackup) {
                app(BackupService::class)->create(BackupService::TYPE_DATABASE, ['source' => 'safety']);
            }

            Artisan::call('down', ['--retry' => 60]);

            $this->archive->extractApproved($zipPath, $workDir, $inspection['entries']);

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

            $merged = 0;
            $replaced = 0;

            if ($shouldPreserve) {
                $authoritative = app()->environment('production')
                    && config('backup.production_login_mode', 'authoritative') === 'authoritative';

                if ($authoritative) {
                    $loginReport = $this->localLogins->restoreAuthoritative();
                    $merged = $loginReport['inserted'];
                    $replaced = $loginReport['replaced'];
                } else {
                    $merged = $this->localLogins->restoreMissing();
                }

                $this->writeLoginAudit($backupId, $merged, $replaced, $authoritative);
            }

            $this->localLogins->ensureBootstrapAdmin();
            $this->invalidateAuthSessions();

            $clearedAiKeys = app(\App\Services\Ai\AiConfigurationService::class)->sanitizeUnreadableApiKeys();

            Artisan::call('up');
            Artisan::call('cache:clear');
            CatalogCache::forgetOverviewStats();
            app(StoreLayoutService::class)->clearCache();

            // Stash merge count for UI flash (optional).
            if ($merged > 0) {
                session()->flash('backup_restore_merged_logins', $merged);
            }
            if ($replaced > 0) {
                session()->flash('backup_restore_replaced_logins', $replaced);
            }

            if ($clearedAiKeys > 0) {
                session()->flash('backup_restore_cleared_ai_keys', $clearedAiKeys);
            }
        } catch (\Throwable $exception) {
            Artisan::call('up');

            throw $exception;
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    private function invalidateAuthSessions(): void
    {
        try {
            Auth::guard('web')->logout();
            Auth::guard('customer')->logout();
        } catch (\Throwable) {
            // Guards may be unavailable mid-request; continue.
        }

        if (Schema::hasTable('sessions') && config('session.driver') === 'database') {
            DB::table('sessions')->delete();
        }
    }

    private function resolveDatabaseFile(string $workDir): ?string
    {
        $driver = config('database.connections.'.config('database.default').'.driver');

        if ($driver === 'sqlite') {
            $path = $workDir.DIRECTORY_SEPARATOR.'database.sqlite';

            return is_file($path) ? $path : null;
        }

        $sqlPath = $workDir.DIRECTORY_SEPARATOR.'database.sql';

        if (is_file($sqlPath)) {
            return $sqlPath;
        }

        // Do not feed SQLite binaries into MySQL importers.
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

    private function writeLoginAudit(string $backupId, int $inserted, int $replaced, bool $authoritative): void
    {
        $directory = storage_path('app/private/backup-restore-audit');
        File::ensureDirectoryExists($directory, 0700);
        $report = [
            'backup_id' => $backupId,
            'restored_at' => now()->toIso8601String(),
            'actor_user_id' => Auth::id(),
            'environment' => app()->environment(),
            'login_mode' => $authoritative ? 'authoritative-local' : 'merge-missing',
            'inserted_accounts' => $inserted,
            'replaced_accounts' => $replaced,
        ];

        File::put(
            $directory.DIRECTORY_SEPARATOR.now()->format('Ymd_His').'_'.bin2hex(random_bytes(4)).'.json',
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );
    }
}
