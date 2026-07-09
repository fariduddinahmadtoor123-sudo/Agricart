<?php

namespace App\Support;

class PublicStorage
{
    /**
     * Whether the public disk is served through Laravel's filesystem route (no working symlink).
     */
    public static function shouldServeViaApplication(): bool
    {
        if (config('filesystems.disks.'.MediaDisk::name().'.driver') !== 'local') {
            return false;
        }

        return (bool) config('filesystems.disks.public.serve', false);
    }

    public static function symlinkAvailable(): bool
    {
        return is_link(public_path('storage'));
    }

    public static function deploymentModeLabel(): string
    {
        if (self::symlinkAvailable()) {
            return 'symlink (web server direct)';
        }

        if (self::shouldServeViaApplication()) {
            return 'Laravel public disk route (/storage/*)';
        }

        return 'unset — configure symlink or FILESYSTEM_PUBLIC_SERVE=auto';
    }
}
