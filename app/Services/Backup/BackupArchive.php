<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

final class BackupArchive
{
    /**
     * Validate every archive entry before any content is read or extracted.
     *
     * @return array{manifest: array<string, mixed>, entries: list<array{index: int, name: string, size: int}>}
     */
    public function inspect(string $path, string $errorBag = 'uploadFile'): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            $this->reject($errorBag, 'invalid_archive');
        }

        $maxEntries = (int) config('backup.archive.max_entries', 100000);
        $maxEntryBytes = (int) config('backup.archive.max_entry_bytes', 2 * 1024 * 1024 * 1024);
        $maxUncompressedBytes = (int) config('backup.archive.max_uncompressed_bytes', 20 * 1024 * 1024 * 1024);
        $maxRatio = (float) config('backup.archive.max_compression_ratio', 200);
        $entries = [];
        $seen = [];
        $critical = [];
        $total = 0;

        try {
            if ($zip->numFiles < 1 || $zip->numFiles > $maxEntries) {
                $this->reject($errorBag, 'archive_limits');
            }

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index, ZipArchive::FL_UNCHANGED);

                if (! is_array($stat)) {
                    $this->reject($errorBag, 'invalid_archive');
                }

                $name = str_replace('\\', '/', (string) ($stat['name'] ?? ''));
                $normalized = $this->approvedName($name, $errorBag);
                $isDirectory = str_ends_with($name, '/');

                if (isset($seen[$normalized])) {
                    $this->reject($errorBag, 'archive_duplicate');
                }

                $seen[$normalized] = true;
                $this->assertRegularEntry($zip, $index, $isDirectory, $errorBag);

                if ($isDirectory) {
                    continue;
                }

                $size = (int) ($stat['size'] ?? -1);
                $compressed = (int) ($stat['comp_size'] ?? -1);

                if ($size < 0 || $compressed < 0 || $size > $maxEntryBytes) {
                    $this->reject($errorBag, 'archive_limits');
                }

                $total += $size;

                if ($total > $maxUncompressedBytes) {
                    $this->reject($errorBag, 'archive_limits');
                }

                if ($size > 0 && ($compressed === 0 || ($size / max(1, $compressed)) > $maxRatio)) {
                    $this->reject($errorBag, 'archive_ratio');
                }

                if (! $this->isAllowedFile($normalized)) {
                    $this->reject($errorBag, 'archive_entry');
                }

                if (in_array($normalized, ['manifest.json', 'database.sql', 'database.sqlite'], true)) {
                    $critical[$normalized] = ($critical[$normalized] ?? 0) + 1;
                }

                $entries[] = ['index' => $index, 'name' => $normalized, 'size' => $size];
            }

            if (($critical['manifest.json'] ?? 0) !== 1) {
                $this->reject($errorBag, 'invalid_manifest');
            }

            $databaseCount = ($critical['database.sql'] ?? 0) + ($critical['database.sqlite'] ?? 0);

            if ($databaseCount !== 1) {
                $this->reject($errorBag, 'database_missing');
            }

            $manifestIndex = collect($entries)->firstWhere('name', 'manifest.json')['index'] ?? null;
            $manifestSize = collect($entries)->firstWhere('name', 'manifest.json')['size'] ?? null;
            $maxManifestBytes = (int) config('backup.archive.max_manifest_bytes', 64 * 1024);

            if (! is_int($manifestSize) || $manifestSize > $maxManifestBytes) {
                $this->reject($errorBag, 'invalid_manifest');
            }

            $manifestRaw = is_int($manifestIndex)
                ? $zip->getFromIndex($manifestIndex, $maxManifestBytes, ZipArchive::FL_UNCHANGED)
                : false;
            $manifest = is_string($manifestRaw) ? json_decode($manifestRaw, true) : null;

            if (! is_array($manifest)
                || ! in_array((string) ($manifest['type'] ?? ''), [BackupService::TYPE_FULL, BackupService::TYPE_DATABASE], true)) {
                $this->reject($errorBag, 'invalid_manifest');
            }

            return ['manifest' => $manifest, 'entries' => $entries];
        } finally {
            $zip->close();
        }
    }

    /**
     * Stream only entries approved by inspect() into a fresh private directory.
     *
     * @param  list<array{index: int, name: string, size: int}>  $entries
     */
    public function extractApproved(string $path, string $destination, array $entries): void
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Unable to open validated backup archive.');
        }

        File::ensureDirectoryExists($destination, 0700);
        $root = realpath($destination);

        try {
            foreach ($entries as $entry) {
                $target = $destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $entry['name']);
                File::ensureDirectoryExists(dirname($target), 0700);
                $parent = realpath(dirname($target));

                if (! is_string($root) || ! is_string($parent) || ! str_starts_with($parent.DIRECTORY_SEPARATOR, $root.DIRECTORY_SEPARATOR)) {
                    throw new RuntimeException('Backup extraction escaped the private working directory.');
                }

                $input = $zip->getStreamIndex($entry['index'], ZipArchive::FL_UNCHANGED);
                $output = @fopen($target, 'xb');

                if (! is_resource($input) || ! is_resource($output)) {
                    if (is_resource($input)) {
                        fclose($input);
                    }
                    if (is_resource($output)) {
                        fclose($output);
                    }
                    throw new RuntimeException('Unable to stream a validated backup entry.');
                }

                $written = stream_copy_to_stream($input, $output, $entry['size'] + 1);
                fclose($input);
                fclose($output);

                if ($written !== $entry['size']) {
                    throw new RuntimeException('Backup entry size changed during extraction.');
                }
            }
        } finally {
            $zip->close();
        }
    }

    private function approvedName(string $name, string $errorBag): string
    {
        if ($name === ''
            || str_contains($name, "\0")
            || str_starts_with($name, '/')
            || preg_match('/^[A-Za-z]:/', $name) === 1
            || preg_match('#(^|/)\.\.(/|$)#', $name) === 1
            || preg_match('#(^|/)\.(/|$)#', $name) === 1) {
            $this->reject($errorBag, 'archive_entry');
        }

        return rtrim($name, '/');
    }

    private function assertRegularEntry(ZipArchive $zip, int $index, bool $directory, string $errorBag): void
    {
        $opsys = 0;
        $attributes = 0;

        if (! $zip->getExternalAttributesIndex($index, $opsys, $attributes, ZipArchive::FL_UNCHANGED)) {
            return;
        }

        if ($opsys === ZipArchive::OPSYS_UNIX) {
            $type = ($attributes >> 16) & 0170000;
            $expected = $directory ? 0040000 : 0100000;

            if ($type !== 0 && $type !== $expected) {
                $this->reject($errorBag, 'archive_entry');
            }
        }
    }

    private function isAllowedFile(string $name): bool
    {
        return in_array($name, ['manifest.json', 'database.sql', 'database.sqlite'], true)
            || str_starts_with($name, 'files/');
    }

    private function reject(string $bag, string $message): never
    {
        throw ValidationException::withMessages([
            $bag => __("agricart.backups.messages.{$message}"),
        ]);
    }
}
