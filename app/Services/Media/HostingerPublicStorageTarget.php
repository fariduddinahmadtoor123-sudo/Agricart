<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\File;

/**
 * Resolves where public media should be mirrored for Apache static serving.
 *
 * Split layout:  {HOSTINGER_PUBLIC_HTML}/storage
 * Direct checkout inside public_html:  {app}/public/storage
 * (never Laravel's storage/ root — that would corrupt private files)
 */
class HostingerPublicStorageTarget
{
    public function resolve(?string $publicHtml = null): ?string
    {
        $publicHtml ??= config('hostinger.public_html');

        if (! is_string($publicHtml) || trim($publicHtml) === '') {
            return null;
        }

        $publicHtml = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $publicHtml), DIRECTORY_SEPARATOR);
        $source = storage_path('app'.DIRECTORY_SEPARATOR.'public');
        $splitTarget = $publicHtml.DIRECTORY_SEPARATOR.'storage';
        $directTarget = public_path('storage');

        if ($this->isDirectCheckout($publicHtml) || $this->isLaravelStorageTree($splitTarget, $source)) {
            return $directTarget;
        }

        return $splitTarget;
    }

    public function isDirectCheckout(?string $publicHtml = null): bool
    {
        $publicHtml ??= config('hostinger.public_html');

        if (! is_string($publicHtml) || trim($publicHtml) === '') {
            return false;
        }

        $publicHtml = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $publicHtml), DIRECTORY_SEPARATOR);
        $base = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, base_path()), DIRECTORY_SEPARATOR);

        $resolvedHtml = realpath($publicHtml) ?: $publicHtml;
        $resolvedBase = realpath($base) ?: $base;

        return strcasecmp($resolvedHtml, $resolvedBase) === 0;
    }

    public function isExactLinkToSource(string $target, ?string $source = null): bool
    {
        $source ??= storage_path('app'.DIRECTORY_SEPARATOR.'public');

        if (! File::exists($target) && ! is_link($target)) {
            return false;
        }

        $resolvedSource = realpath($source);
        $resolvedTarget = realpath($target);

        return is_string($resolvedSource)
            && is_string($resolvedTarget)
            && strcasecmp($resolvedSource, $resolvedTarget) === 0;
    }

    public function isLaravelStorageTree(string $target, ?string $source = null): bool
    {
        $source ??= storage_path('app'.DIRECTORY_SEPARATOR.'public');
        $resolvedTarget = realpath($target);
        $resolvedLaravelStorage = realpath(storage_path());
        $resolvedSource = realpath($source);

        if (! is_string($resolvedTarget) || ! is_string($resolvedLaravelStorage)) {
            return false;
        }

        if (strcasecmp($resolvedTarget, $resolvedLaravelStorage) === 0) {
            return true;
        }

        // Exact match to storage/app/public is a valid mirror/link target, not the storage root.
        if (is_string($resolvedSource) && strcasecmp($resolvedTarget, $resolvedSource) === 0) {
            return false;
        }

        return is_string($resolvedSource)
            && str_starts_with(
                strtolower(rtrim($resolvedSource, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR),
                strtolower(rtrim($resolvedTarget, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR),
            );
    }
}
