<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Central disk name for catalog, store, and contact uploads (config: filesystems.media_disk).
 */
class MediaDisk
{
    public static function name(): string
    {
        $name = (string) config('filesystems.media_disk', 'public');

        return config("filesystems.disks.{$name}") !== null ? $name : 'public';
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(self::name());
    }

    public static function url(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return self::disk()->url($path);
    }
}
