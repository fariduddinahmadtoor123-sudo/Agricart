<?php

namespace App\Services\PortableArchive;

use App\Services\Media\PublicMediaMirror;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

/**
 * Shared encrypted portable archive helpers (.ags).
 *
 * New exports use AGS2: chunked AES-256-GCM so large archives (10k+ categories
 * with media) do not require loading the entire zip into PHP memory.
 *
 * Legacy AGS1 (single-shot GCM of the whole zip) remains readable for import.
 *
 * ZIP contains manifest.json, records/catalog JSON, and optional media/.
 */
class PortableArchiveCodec
{
    public const EXTENSION = 'ags';

    /** Legacy single-buffer AES-GCM envelope (still accepted on import). */
    public const MAGIC = 'AGS1';

    /** Chunked AES-GCM envelope used for new exports. */
    public const MAGIC_V2 = 'AGS2';

    /** Plaintext bytes per GCM chunk when sealing AGS2. */
    public const SEAL_CHUNK_BYTES = 1_048_576;

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

    /**
     * Allow long-running export/import and raise memory when the host permits it.
     */
    public function prepareLargeArchiveOperation(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        @ini_set('max_execution_time', '0');

        $limit = strtoupper((string) ini_get('memory_limit'));
        if ($limit !== '-1' && $this->memoryLimitBytes($limit) < 1024 * 1024 * 1024) {
            @ini_set('memory_limit', '1024M');
        }
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

        if (app()->environment(['local', 'testing'])) {
            return hash('sha256', 'agricart.portable.archive.v1', true);
        }

        throw ValidationException::withMessages([
            'archive' => [__('agricart.portable.key_required')],
        ]);
    }

    public function isSupportedMagic(string $magic): bool
    {
        return $magic === self::MAGIC || $magic === self::MAGIC_V2;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  list<array<string, mixed>>  $records
     * @return array{path: string, filename: string}
     */
    public function sealDirectory(string $workDir, string $recordsJsonName, array $manifest, array $records, string $filename): array
    {
        $this->prepareLargeArchiveOperation();

        File::put(
            $workDir.'/manifest.json',
            json_encode($manifest, JSON_UNESCAPED_UNICODE),
        );
        File::put(
            $workDir.'/'.$recordsJsonName,
            json_encode($records, JSON_UNESCAPED_UNICODE),
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
        $this->prepareLargeArchiveOperation();

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
        $this->prepareLargeArchiveOperation();

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
        if (! filled($storagePath) || ! Storage::disk('public')->exists($storagePath)) {
            return null;
        }

        $ext = pathinfo((string) $storagePath, PATHINFO_EXTENSION) ?: 'bin';
        $relative = 'media/'.$folderKey.'/'.$role.'.'.$ext;
        $absolute = $workDir.'/'.$relative;
        File::ensureDirectoryExists(dirname($absolute));

        $stream = Storage::disk('public')->readStream($storagePath);
        if ($stream === false) {
            return null;
        }

        $out = fopen($absolute, 'wb');
        if ($out === false) {
            if (is_resource($stream)) {
                fclose($stream);
            }

            return null;
        }

        stream_copy_to_stream($stream, $out);
        fclose($out);
        if (is_resource($stream)) {
            fclose($stream);
        }

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

        $in = fopen($source, 'rb');
        if ($in === false) {
            return null;
        }

        Storage::disk('public')->writeStream($dest, $in);
        if (is_resource($in)) {
            fclose($in);
        }

        // Keep Apache-facing public/storage (Hostinger mirror) in sync — otherwise
        // catalog imports serve every image through PHP with no-cache headers.
        app(PublicMediaMirror::class)->publish($dest, 'public');

        return $dest;
    }

    /**
     * Seal zip → .ags using chunked AGS2 (constant memory).
     */
    protected function sealFile(string $zipPath, string $destPath): void
    {
        $in = fopen($zipPath, 'rb');
        $out = fopen($destPath, 'wb');

        if ($in === false || $out === false) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.export_failed')],
            ]);
        }

        try {
            $key = $this->archiveKey();
            fwrite($out, self::MAGIC_V2);
            fwrite($out, pack('C', 1));
            fwrite($out, pack('N', self::SEAL_CHUNK_BYTES));

            while (! feof($in)) {
                $plain = fread($in, self::SEAL_CHUNK_BYTES);
                if ($plain === false || $plain === '') {
                    break;
                }

                $iv = random_bytes(12);
                $tag = '';
                $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

                if ($cipher === false || strlen($tag) !== 16) {
                    throw ValidationException::withMessages([
                        'archive' => [__('agricart.portable.export_failed')],
                    ]);
                }

                fwrite($out, $iv.$tag.pack('N', strlen($cipher)).$cipher);
            }
        } finally {
            fclose($in);
            fclose($out);
        }
    }

    /**
     * Unseal .ags → zip. Supports AGS2 (chunked) and legacy AGS1 (whole-buffer).
     */
    protected function unsealFile(string $archivePath, string $zipPath): void
    {
        $in = fopen($archivePath, 'rb');
        if ($in === false) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_missing')],
            ]);
        }

        try {
            $magic = fread($in, 4);
            if (! is_string($magic) || ! $this->isSupportedMagic($magic)) {
                throw ValidationException::withMessages([
                    'archive' => [__('agricart.portable.import_invalid')],
                ]);
            }

            if ($magic === self::MAGIC_V2) {
                $this->unsealFileV2($in, $zipPath);

                return;
            }

            $this->unsealFileV1($in, $zipPath);
        } finally {
            if (is_resource($in)) {
                fclose($in);
            }
        }
    }

    /**
     * @param  resource  $in
     */
    protected function unsealFileV2($in, string $zipPath): void
    {
        $version = fread($in, 1);
        $chunkSizeRaw = fread($in, 4);

        if ($version === false || $chunkSizeRaw === false || strlen($chunkSizeRaw) !== 4) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_truncated')],
            ]);
        }

        $versionByte = unpack('C', $version)[1] ?? 0;
        $declaredChunk = unpack('N', $chunkSizeRaw)[1] ?? 0;

        if ($versionByte !== 1 || $declaredChunk < 1024 || $declaredChunk > 16 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_invalid')],
            ]);
        }

        $out = fopen($zipPath, 'wb');
        if ($out === false) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_invalid')],
            ]);
        }

        $key = $this->archiveKey();

        try {
            while (! feof($in)) {
                $iv = fread($in, 12);
                if ($iv === false || $iv === '') {
                    break;
                }
                if (strlen($iv) !== 12) {
                    throw ValidationException::withMessages([
                        'archive' => [__('agricart.portable.import_truncated')],
                    ]);
                }

                $tag = fread($in, 16);
                $lenRaw = fread($in, 4);
                if ($tag === false || $lenRaw === false || strlen($tag) !== 16 || strlen($lenRaw) !== 4) {
                    throw ValidationException::withMessages([
                        'archive' => [__('agricart.portable.import_truncated')],
                    ]);
                }

                $cipherLen = unpack('N', $lenRaw)[1] ?? 0;
                if ($cipherLen < 1 || $cipherLen > $declaredChunk + 64) {
                    throw ValidationException::withMessages([
                        'archive' => [__('agricart.portable.import_invalid')],
                    ]);
                }

                $cipher = fread($in, $cipherLen);
                if ($cipher === false || strlen($cipher) !== $cipherLen) {
                    throw ValidationException::withMessages([
                        'archive' => [__('agricart.portable.import_truncated')],
                    ]);
                }

                $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
                if ($plain === false) {
                    throw ValidationException::withMessages([
                        'archive' => [__('agricart.portable.import_decrypt')],
                    ]);
                }

                fwrite($out, $plain);
            }
        } finally {
            fclose($out);
        }
    }

    /**
     * @param  resource  $in  Positioned after the AGS1 magic bytes.
     */
    protected function unsealFileV1($in, string $zipPath): void
    {
        $body = stream_get_contents($in);
        if ($body === false || strlen($body) < 12 + 16 + 1) {
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

    private function memoryLimitBytes(string $limit): int
    {
        if ($limit === '' || $limit === '-1') {
            return PHP_INT_MAX;
        }

        $unit = substr($limit, -1);
        $value = (int) $limit;

        return match (strtoupper($unit)) {
            'G' => $value * 1024 * 1024 * 1024,
            'M' => $value * 1024 * 1024,
            'K' => $value * 1024,
            default => (int) $limit,
        };
    }
}
