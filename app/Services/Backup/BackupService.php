<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class BackupService
{
    public const TYPE_FULL = 'full';

    public const TYPE_DATABASE = 'database';

    public function __construct(
        private readonly DatabaseDumper $databaseDumper,
    ) {}

    public function create(string $type): BackupEntry
    {
        if (! in_array($type, [self::TYPE_FULL, self::TYPE_DATABASE], true)) {
            throw ValidationException::withMessages([
                'backup' => __('agricart.backups.messages.invalid_type'),
            ]);
        }

        set_time_limit(0);

        $id = now()->format('Y_m_d_His') . '_' . $type;
        $workDir = storage_path('app/backup-work-' . $id);
        $zipFilename = $id . '.zip';
        $zipPath = $this->absolutePath($zipFilename);

        File::ensureDirectoryExists(dirname($zipPath));
        File::ensureDirectoryExists($workDir);

        try {
            $databasePath = $workDir . DIRECTORY_SEPARATOR . $this->databaseFilename();
            $this->databaseDumper->dumpTo($databasePath);

            $filesCount = 0;
            $publicBytes = 0;

            if ($type === self::TYPE_FULL) {
                [$filesCount, $publicBytes] = $this->measurePublicFiles();
                $estimatedBytes = filesize($databasePath) + $publicBytes;

                if ($estimatedBytes > (int) config('backup.max_file_bytes')) {
                    throw ValidationException::withMessages([
                        'backup' => __('agricart.backups.messages.too_large', [
                            'limit' => BackupEntry::formatBytes((int) config('backup.max_file_bytes')),
                        ]),
                    ]);
                }
            }

            $manifest = [
                'id' => $id,
                'type' => $type,
                'created_at' => now()->toIso8601String(),
                'app_name' => config('app.name'),
                'laravel_version' => app()->version(),
                'database_driver' => config('database.connections.' . config('database.default') . '.driver'),
                'files_count' => $filesCount,
            ];

            file_put_contents(
                $workDir . DIRECTORY_SEPARATOR . 'manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            );

            $zip = new ZipArchive;

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw ValidationException::withMessages([
                    'backup' => __('agricart.backups.messages.zip_failed'),
                ]);
            }

            $zip->addFile($databasePath, $this->databaseFilename());
            $zip->addFile($workDir . DIRECTORY_SEPARATOR . 'manifest.json', 'manifest.json');

            if ($type === self::TYPE_FULL) {
                $this->addPublicFilesToZip($zip);
            }

            $zip->close();

            if (! is_file($zipPath)) {
                throw ValidationException::withMessages([
                    'backup' => __('agricart.backups.messages.zip_failed'),
                ]);
            }

            $this->enforceRetention();

            return $this->entryFromZip($zipPath);
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    /**
     * @return list<BackupEntry>
     */
    public function list(): array
    {
        $files = glob($this->absolutePath('*.zip')) ?: [];
        rsort($files);

        $entries = [];

        foreach ($files as $path) {
            $entry = $this->entryFromZip($path);

            if ($entry !== null) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    public function find(string $id): ?BackupEntry
    {
        $path = $this->absolutePath($id . '.zip');

        if (! is_file($path)) {
            return null;
        }

        return $this->entryFromZip($path);
    }

    public function absolutePath(string $filename): string
    {
        return Storage::disk((string) config('backup.disk', 'local'))
            ->path(trim((string) config('backup.directory', 'agricart-backups'), '/') . '/' . ltrim($filename, '/'));
    }

    public function importFromUpload(string $sourcePath): BackupEntry
    {
        set_time_limit(0);

        if (! is_file($sourcePath)) {
            throw ValidationException::withMessages([
                'uploadFile' => __('agricart.backups.messages.upload_missing'),
            ]);
        }

        $size = (int) filesize($sourcePath);
        $maxFile = (int) config('backup.max_file_bytes');

        if ($size > $maxFile) {
            throw ValidationException::withMessages([
                'uploadFile' => __('agricart.backups.messages.too_large', [
                    'limit' => BackupEntry::formatBytes($maxFile),
                ]),
            ]);
        }

        $maxTotal = (int) config('backup.max_total_bytes');

        if ($this->totalSizeBytes() + $size > $maxTotal) {
            throw ValidationException::withMessages([
                'uploadFile' => __('agricart.backups.messages.total_limit_exceeded', [
                    'limit' => BackupEntry::formatBytes($maxTotal),
                ]),
            ]);
        }

        $manifest = $this->validateBackupZip($sourcePath);
        $newId = 'imported_' . now()->format('Y_m_d_His');
        $destPath = $this->absolutePath($newId . '.zip');

        File::ensureDirectoryExists(dirname($destPath));

        if (! copy($sourcePath, $destPath)) {
            throw ValidationException::withMessages([
                'uploadFile' => __('agricart.backups.messages.upload_failed'),
            ]);
        }

        $this->rewriteManifest($destPath, $newId, $manifest);
        $this->enforceRetention();

        $entry = $this->entryFromZip($destPath);

        if ($entry === null) {
            if (is_file($destPath)) {
                unlink($destPath);
            }

            throw ValidationException::withMessages([
                'uploadFile' => __('agricart.backups.messages.invalid_archive'),
            ]);
        }

        return $entry;
    }

    public function incomingDirectoryAbsolute(): string
    {
        $base = dirname($this->absolutePath('placeholder.zip'));
        $subdir = trim((string) config('backup.incoming_subdir', 'incoming'), '/');
        $path = $base.DIRECTORY_SEPARATOR.$subdir;

        File::ensureDirectoryExists($path);
        File::ensureDirectoryExists($path.DIRECTORY_SEPARATOR.'processed');

        return $path;
    }

    /**
     * Import .zip files placed in the incoming folder (FTP / file manager).
     *
     * @return list<array{filename: string, ok: bool, message: string}>
     */
    public function importIncomingArchives(): array
    {
        set_time_limit(0);

        $incoming = $this->incomingDirectoryAbsolute();
        $processed = $incoming.DIRECTORY_SEPARATOR.'processed';
        $results = [];

        foreach (glob($incoming.DIRECTORY_SEPARATOR.'*.zip') ?: [] as $zipPath) {
            $name = basename($zipPath);

            try {
                $entry = $this->importFromUpload($zipPath);
                $processedPath = $processed.DIRECTORY_SEPARATOR.$name;

                if (is_file($processedPath)) {
                    $processedPath = $processed.DIRECTORY_SEPARATOR.pathinfo($name, PATHINFO_FILENAME)
                        .'_'.now()->format('His').'.zip';
                }

                if (! @rename($zipPath, $processedPath)) {
                    @unlink($zipPath);
                }

                $results[] = [
                    'filename' => $name,
                    'ok' => true,
                    'message' => __('agricart.backups.messages.incoming_imported', [
                        'name' => $name,
                        'id' => $entry->id,
                    ]),
                ];
            } catch (ValidationException $exception) {
                $results[] = [
                    'filename' => $name,
                    'ok' => false,
                    'message' => collect($exception->errors())->flatten()->first() ?: $name,
                ];
            } catch (\Throwable $exception) {
                report($exception);

                $results[] = [
                    'filename' => $name,
                    'ok' => false,
                    'message' => __('agricart.backups.messages.incoming_failed', ['name' => $name]),
                ];
            }
        }

        return $results;
    }

    public function delete(string $id): void
    {
        $path = $this->absolutePath($id . '.zip');

        if (is_file($path)) {
            unlink($path);
        }
    }

    public function totalSizeBytes(): int
    {
        $files = glob($this->absolutePath('*.zip')) ?: [];

        return array_sum(array_map(static fn (string $path): int => (int) filesize($path), $files));
    }

    private function databaseFilename(): string
    {
        $driver = config('database.connections.' . config('database.default') . '.driver');

        return $driver === 'sqlite' ? 'database.sqlite' : 'database.sql';
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function measurePublicFiles(): array
    {
        $root = (string) config('backup.public_root', storage_path('app/public'));

        if (! is_dir($root)) {
            return [0, 0];
        }

        $count = 0;
        $bytes = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $count++;
            $bytes += $file->getSize();
        }

        return [$count, $bytes];
    }

    private function addPublicFilesToZip(ZipArchive $zip): void
    {
        $root = (string) config('backup.public_root', storage_path('app/public'));

        if (! is_dir($root)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relative = ltrim(str_replace($root, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $zipPath = 'files/' . str_replace(DIRECTORY_SEPARATOR, '/', $relative);
            $zip->addFile($file->getPathname(), $zipPath);
        }
    }

    private function entryFromZip(string $path): ?BackupEntry
    {
        if (! is_file($path)) {
            return null;
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return null;
        }

        $manifestRaw = $zip->getFromName('manifest.json');
        $zip->close();

        $manifest = is_string($manifestRaw)
            ? json_decode($manifestRaw, true)
            : null;

        $filename = basename($path);
        $id = (string) ($manifest['id'] ?? str_replace('.zip', '', $filename));

        return new BackupEntry(
            id: $id,
            type: (string) ($manifest['type'] ?? 'unknown'),
            filename: $filename,
            sizeBytes: (int) filesize($path),
            createdAt: (string) ($manifest['created_at'] ?? date('c', filemtime($path))),
            filesCount: (int) ($manifest['files_count'] ?? 0),
            meta: is_array($manifest) ? $manifest : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validateBackupZip(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages([
                'uploadFile' => __('agricart.backups.messages.invalid_archive'),
            ]);
        }

        $manifestRaw = $zip->getFromName('manifest.json');

        if (! is_string($manifestRaw)) {
            $zip->close();

            throw ValidationException::withMessages([
                'uploadFile' => __('agricart.backups.messages.invalid_manifest'),
            ]);
        }

        $manifest = json_decode($manifestRaw, true);

        if (! is_array($manifest)) {
            $zip->close();

            throw ValidationException::withMessages([
                'uploadFile' => __('agricart.backups.messages.invalid_manifest'),
            ]);
        }

        $type = (string) ($manifest['type'] ?? '');

        if (! in_array($type, [self::TYPE_FULL, self::TYPE_DATABASE], true)) {
            $zip->close();

            throw ValidationException::withMessages([
                'uploadFile' => __('agricart.backups.messages.invalid_type'),
            ]);
        }

        $hasDatabase = $zip->locateName('database.sql') !== false
            || $zip->locateName('database.sqlite') !== false;

        $zip->close();

        if (! $hasDatabase) {
            throw ValidationException::withMessages([
                'uploadFile' => __('agricart.backups.messages.database_missing'),
            ]);
        }

        return $manifest;
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function rewriteManifest(string $zipPath, string $newId, array $manifest): void
    {
        $manifest['source_id'] = $manifest['id'] ?? null;
        $manifest['id'] = $newId;
        $manifest['imported_at'] = now()->toIso8601String();

        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw ValidationException::withMessages([
                'uploadFile' => __('agricart.backups.messages.zip_failed'),
            ]);
        }

        $zip->deleteName('manifest.json');
        $zip->addFromString(
            'manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );
        $zip->close();
    }

    private function enforceRetention(): void
    {
        $entries = $this->list();
        $maxCount = (int) config('backup.retention_count', 10);
        $maxTotal = (int) config('backup.max_total_bytes');

        if (count($entries) > $maxCount) {
            foreach (array_slice($entries, $maxCount) as $entry) {
                $this->delete($entry->id);
            }
        }

        while ($this->totalSizeBytes() > $maxTotal && count($this->list()) > 1) {
            $remaining = $this->list();
            $oldest = $remaining[array_key_last($remaining)] ?? null;

            if ($oldest === null) {
                break;
            }

            $this->delete($oldest->id);
        }
    }
}
