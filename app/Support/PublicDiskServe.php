<?php

namespace App\Support;

/**
 * Decides whether Laravel should register GET /storage/{path} for the public disk.
 *
 * On Laragon/VPS a working public/storage symlink lets Apache serve files directly.
 * On Hostinger, PHP can often create/read that symlink while Apache still cannot
 * follow it for static files — so the Laravel route must stay registered.
 */
class PublicDiskServe
{
    public static function enabled(
        ?string $mode = null,
        ?bool $symlinkPresent = null,
        ?string $hostingerPublicHtml = null,
    ): bool {
        $mode = strtolower(trim((string) ($mode ?? env('FILESYSTEM_PUBLIC_SERVE', 'auto'))));
        $symlinkPresent ??= is_link(public_path('storage'));
        $hostingerPublicHtml ??= env('HOSTINGER_PUBLIC_HTML');
        $hostingerConfigured = is_string($hostingerPublicHtml) && trim($hostingerPublicHtml) !== '';

        return match (true) {
            in_array($mode, ['1', 'true', 'yes', 'on'], true) => true,
            in_array($mode, ['0', 'false', 'no', 'off'], true) => false,
            $hostingerConfigured => true,
            default => ! $symlinkPresent,
        };
    }
}
