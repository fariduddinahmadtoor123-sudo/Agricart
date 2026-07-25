<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Keeps Hostinger's public_html/storage mirror in sync with storage/app/public
 * after each upload/delete. On Laragon (symlink) or when HOSTINGER_PUBLIC_HTML
 * is unset, this is a no-op.
 */
class PublicMediaMirror
{
    public function isActive(): bool
    {
        return $this->mirrorRoot() !== null;
    }

    /**
     * Absolute path to public_html/storage when a real directory mirror is in use.
     */
    public function mirrorRoot(): ?string
    {
        if (! filter_var(config('hostinger.mirror_public_media_on_write', true), FILTER_VALIDATE_BOOL)) {
            return null;
        }

        $publicHtml = config('hostinger.public_html');

        if (! is_string($publicHtml) || trim($publicHtml) === '') {
            return null;
        }

        $publicHtml = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $publicHtml), DIRECTORY_SEPARATOR);
        $target = $publicHtml.DIRECTORY_SEPARATOR.'storage';
        $source = storage_path('app'.DIRECTORY_SEPARATOR.'public');

        if ($this->isLinkOrJunction($target)) {
            $resolvedSource = realpath($source);
            $resolvedTarget = realpath($target);

            if (
                is_string($resolvedSource)
                && is_string($resolvedTarget)
                && strcasecmp($resolvedSource, $resolvedTarget) === 0
            ) {
                // Correct symlink — Apache already sees live files.
                return null;
            }

            return null;
        }

        if (File::exists($target) && ! File::isDirectory($target)) {
            return null;
        }

        $resolvedTarget = realpath($target);
        $resolvedLaravelStorage = realpath(storage_path());
        $resolvedSource = realpath($source);

        if (
            is_string($resolvedTarget)
            && is_string($resolvedLaravelStorage)
            && (
                strcasecmp($resolvedTarget, $resolvedLaravelStorage) === 0
                || (
                    is_string($resolvedSource)
                    && str_starts_with(
                        strtolower(rtrim($resolvedSource, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR),
                        strtolower(rtrim($resolvedTarget, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR),
                    )
                )
            )
        ) {
            // Never treat Laravel's storage root as a public mirror.
            return null;
        }

        // Mirror mode: public_html/storage is (or will be) a real directory.
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
