<?php

namespace App\Services\PortableArchive;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

/**
 * Shared encrypted portable archive helpers (.ags).
 *
 * File layout: magic "AGS1" + 12-byte IV + 16-byte GCM tag + AES-256-GCM(zip).
 * ZIP contains manifest.json, a records JSON file, and optional media/.
 */
class PortableArchiveCodec
{
    public const EXTENSION = 'ags';

    public const MAGIC = 'AGS1';

    /**
     * @param  list<string>  $scopeParts  e.g. ['customers'], ['customers','online'], ['suppliers']
     */
    public function datedFilename(array $scopeParts): string
    {
        $parts = array_values(array_filter(array_map(
            static fn (string $part): string => Str::slug($part, '-'),
            $scopeParts,
        )));

        if ($parts === []) {
            $parts = ['archive'];
        }

        return implode('-', $parts).'-'.now()->format('Y-m-d').'.'.self::EXTENSION;
    }

    public function archiveKey(): string
    {
        $configured = (string) config('agricart.portable_archive_key', '');

        if ($configured === '') {
            $configured = (string) config('agricart.supplier_archive_key', '');
        }

        if ($configured !== '') {
            return hash('sha256', $configured, true);
        }

        return hash('sha256', 'agricart.portable.archive.v1', true);
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  list<array<string, mixed>>  $records
     * @return array{path: string, filename: string}
     */
    public function sealDirectory(string $workDir, string $recordsJsonName, array $manifest, array $records, string $filename): array
    {
        File::put(
            $workDir.'/manifest.json',
            json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        );
        File::put(
            $workDir.'/'.$recordsJsonName,
            json_encode($records, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        );

        $zipPath = $workDir.'.zip';
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.export_failed')],
            ]);
        }

        $this->addDirectoryToZip($zip, $workDir, $workDir);
        $zip->close();

        $sealedPath = storage_path('app/tmp/'.$filename);
        File::ensureDirectoryExists(dirname($sealedPath));
        $this->sealFile($zipPath, $sealedPath);
        File::delete($zipPath);

        return [
            'path' => $sealedPath,
            'filename' => $filename,
        ];
    }

    /**
     * @return array{work_dir: string, extracted: string}
     */
    public function unsealToExtracted(string $archivePath): array
    {
        if (! is_file($archivePath)) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_missing')],
            ]);
        }

        $workDir = storage_path('app/tmp/portable-import-'.Str::lower(Str::random(8)));
        File::ensureDirectoryExists($workDir);
        $zipPath = $workDir.'/payload.zip';
        $this->unsealFile($archivePath, $zipPath);

        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            File::deleteDirectory($workDir);
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_invalid')],
            ]);
        }

        $extracted = $workDir.'/extracted';
        $zip->extractTo($extracted);
        $zip->close();
        File::delete($zipPath);

        return [
            'work_dir' => $workDir,
            'extracted' => $extracted,
        ];
    }

    /**
     * @return array{manifest: array<string, mixed>, records: list<array<string, mixed>>}
     */
    public function readManifestAndRecords(string $extractedDir, string $expectedFormat, string $recordsJsonName): array
    {
        $manifestPath = $extractedDir.'/manifest.json';
        $recordsPath = $extractedDir.'/'.$recordsJsonName;

        if (! is_file($manifestPath) || ! is_file($recordsPath)) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_invalid')],
            ]);
        }

        /** @var array<string, mixed> $manifest */
        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

        if (($manifest['format'] ?? null) !== $expectedFormat) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_format')],
            ]);
        }

        /** @var list<array<string, mixed>>|null $records */
        $records = json_decode((string) file_get_contents($recordsPath), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($records)) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_invalid')],
            ]);
        }

        return [
            'manifest' => $manifest,
            'records' => $records,
        ];
    }

    /**
     * Zip and encrypt an already-prepared work directory (manifest + any JSON/media files).
     *
     * @return array{path: string, filename: string}
     */
    public function sealWorkDir(string $workDir, string $filename): array
    {
        $zipPath = $workDir.'.zip';
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.export_failed')],
            ]);
        }

        $this->addDirectoryToZip($zip, $workDir, $workDir);
        $zip->close();

        $sealedPath = storage_path('app/tmp/'.$filename);
        File::ensureDirectoryExists(dirname($sealedPath));
        $this->sealFile($zipPath, $sealedPath);
        File::delete($zipPath);

        return [
            'path' => $sealedPath,
            'filename' => $filename,
        ];
    }

    public function copyMediaIntoArchive(string $workDir, string $folderKey, string $role, ?string $storagePath): ?string
    {
        if (! filled($storagePath) || ! \Storage::disk('public')->exists($storagePath)) {
            return null;
        }

        $ext = pathinfo((string) $storagePath, PATHINFO_EXTENSION) ?: 'bin';
        $relative = 'media/'.$folderKey.'/'.$role.'.'.$ext;
        File::ensureDirectoryExists($workDir.'/media/'.$folderKey);
        File::put($workDir.'/'.$relative, \Storage::disk('public')->get($storagePath));

        return $relative;
    }

    public function restoreMediaFile(string $extractedRoot, ?string $relative, string $directory): ?string
    {
        if (! filled($relative)) {
            return null;
        }

        $source = $extractedRoot.'/'.ltrim(str_replace('\\', '/', (string) $relative), '/');

        if (! is_file($source)) {
            return null;
        }

        $ext = pathinfo($source, PATHINFO_EXTENSION) ?: 'bin';
        $dest = $directory.'/'.Str::lower(Str::random(40)).'.'.$ext;
        \Storage::disk('public')->put($dest, (string) file_get_contents($source));

        return $dest;
    }

    protected function sealFile(string $zipPath, string $destPath): void
    {
        $plaintext = (string) file_get_contents($zipPath);
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $this->archiveKey(), OPENSSL_RAW_DATA, $iv, $tag);

        if ($cipher === false || strlen($tag) !== 16) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.export_failed')],
            ]);
        }

        file_put_contents($destPath, self::MAGIC.$iv.$tag.$cipher);
    }

    protected function unsealFile(string $archivePath, string $zipPath): void
    {
        $raw = (string) file_get_contents($archivePath);

        if (! str_starts_with($raw, self::MAGIC)) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_invalid')],
            ]);
        }

        $body = substr($raw, strlen(self::MAGIC));

        if (strlen($body) < 12 + 16 + 1) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_truncated')],
            ]);
        }

        $iv = substr($body, 0, 12);
        $tag = substr($body, 12, 16);
        $cipher = substr($body, 28);
        $plaintext = openssl_decrypt($cipher, 'aes-256-gcm', $this->archiveKey(), OPENSSL_RAW_DATA, $iv, $tag);

        if ($plaintext === false) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_decrypt')],
            ]);
        }

        file_put_contents($zipPath, $plaintext);
    }

    protected function addDirectoryToZip(ZipArchive $zip, string $root, string $path): void
    {
        $items = scandir($path) ?: [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full = $path.DIRECTORY_SEPARATOR.$item;
            $local = ltrim(str_replace('\\', '/', substr($full, strlen($root))), '/');

            if (is_dir($full)) {
                $zip->addEmptyDir($local);
                $this->addDirectoryToZip($zip, $root, $full);

                continue;
            }

            $zip->addFile($full, $local);
        }
    }
}
