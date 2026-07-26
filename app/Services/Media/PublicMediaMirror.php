<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Keeps the Apache-facing public storage mirror in sync with storage/app/public
 * after each upload/delete. On Laragon (working symlink) this is a no-op.
 *
 * Split Hostinger layout: public_html/storage
 * Direct checkout in public_html: public/storage (real directory, not Laravel storage/)
 */
class PublicMediaMirror
{
    public function __construct(
        private readonly HostingerPublicStorageTarget $targets,
    ) {}

    public function isActive(): bool
    {
        return $this->mirrorRoot() !== null;
    }

    /**
     * Absolute path to the Apache-facing storage mirror when a real directory is in use.
     */
    public function mirrorRoot(): ?string
    {
        if (! filter_var(config('hostinger.mirror_public_media_on_write', true), FILTER_VALIDATE_BOOL)) {
            return null;
        }

        $target = $this->targets->resolve();

        if ($target === null) {
            return null;
        }

        $source = storage_path('app'.DIRECTORY_SEPARATOR.'public');

        // Symlink/junction already pointing at the live files — nothing to mirror into.
        if ($this->targets->isExactLinkToSource($target, $source)) {
            return null;
        }

        if ($this->isLinkOrJunction($target) && File::exists($target)) {
            return null;
        }

        if (File::exists($target) && ! File::isDirectory($target)) {
            return null;
        }

        if ($this->targets->isLaravelStorageTree($target, $source)) {
            return null;
        }

        return $target;
    }

    public function publish(string $relativePath, string $disk = 'public'): bool
    {
        $relative = $this->normalizeRelative($relativePath);

        if ($relative === null || ! $this->isPublicDisk($disk) || $this->isExcluded($relative)) {
            return false;
        }

        $mirrorRoot = $this->mirrorRoot();

        if ($mirrorRoot === null) {
            return false;
        }

        try {
            $storage = Storage::disk('public');

            if (! $storage->exists($relative)) {
                return false;
            }

            $source = $storage->path($relative);
            $dest = $mirrorRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

            File::ensureDirectoryExists(dirname($dest));

            return File::copy($source, $dest);
        } catch (\Throwable $exception) {
            Log::warning('Public media mirror publish failed.', [
                'path' => $relative,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function forget(string $relativePath, string $disk = 'public'): bool
    {
        $relative = $this->normalizeRelative($relativePath);

        if ($relative === null || ! $this->isPublicDisk($disk) || $this->isExcluded($relative)) {
            return false;
        }

        $mirrorRoot = $this->mirrorRoot();

        if ($mirrorRoot === null) {
            return false;
        }

        try {
            $dest = $mirrorRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

            if (! File::exists($dest) || File::isDirectory($dest)) {
                return false;
            }

            return File::delete($dest);
        } catch (\Throwable $exception) {
            Log::warning('Public media mirror forget failed.', [
                'path' => $relative,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function isPublicDisk(string $disk): bool
    {
        $mediaDisk = (string) config('media.disk', 'public');

        return $disk === 'public' || $disk === $mediaDisk;
    }

    private function normalizeRelative(string $path): ?string
    {
        $relative = ltrim(str_replace('\\', '/', $path), '/');

        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        return $relative;
    }

    private function isExcluded(string $relative): bool
    {
        if (str_starts_with($relative, '.') || str_contains($relative, '/.')) {
            return true;
        }

        foreach ((array) config('hostinger.storage_mirror_exclusions', []) as $prefix) {
            $prefix = trim(str_replace('\\', '/', (string) $prefix), '/');

            if ($prefix !== '' && ($relative === $prefix || str_starts_with($relative, $prefix.'/'))) {
                return true;
            }
        }

        return false;
    }

    private function isLinkOrJunction(string $path): bool
    {
        if (! File::exists($path) && ! is_link($path)) {
            return false;
        }

        if (is_link($path)) {
            return true;
        }

        $resolved = realpath($path);
        $parent = realpath(dirname($path));

        if (! is_string($resolved) || ! is_string($parent)) {
            return false;
        }

        $expected = $parent.DIRECTORY_SEPARATOR.basename($path);

        return strcasecmp(
            rtrim($resolved, DIRECTORY_SEPARATOR),
            rtrim($expected, DIRECTORY_SEPARATOR),
        ) !== 0;
    }
}
