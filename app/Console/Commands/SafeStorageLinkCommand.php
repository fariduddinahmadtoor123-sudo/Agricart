<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Throwable;

class SafeStorageLinkCommand extends Command
{
    protected $signature = 'storage:link {--relative : Retained for Laravel command compatibility}';

    protected $description = 'Create configured storage links without requiring the disabled PHP exec function';

    public function handle(Filesystem $files): int
    {
        $links = config('filesystems.links', [
            public_path('storage') => storage_path('app/public'),
        ]);

        foreach ($links as $link => $target) {
            if (is_link($link) || $this->isLinkedDirectory($link, $target)) {
                $this->components->info("The [{$link}] link already exists.");

                continue;
            }

            if (file_exists($link)) {
                $this->components->error("The [{$link}] path exists and is not a symbolic link.");

                return self::FAILURE;
            }

            $files->ensureDirectoryExists(dirname($link));

            if (! function_exists('symlink')) {
                $this->components->error('PHP symlink() is disabled. Create the link from the hosting shell: ln -s ../storage/app/public public/storage');

                return self::FAILURE;
            }

            try {
                $created = @symlink($target, $link);
            } catch (Throwable) {
                $created = false;
            }

            if (! $created) {
                $this->components->error("Unable to create [{$link}]. Create it from the hosting shell: ln -s ../storage/app/public public/storage");

                return self::FAILURE;
            }

            $this->components->info("The [{$link}] link has been connected to [{$target}].");
        }

        return self::SUCCESS;
    }

    private function isLinkedDirectory(string $link, string $target): bool
    {
        $resolvedLink = realpath($link);
        $resolvedTarget = realpath($target);

        return is_string($resolvedLink)
            && is_string($resolvedTarget)
            && strcasecmp($resolvedLink, $resolvedTarget) === 0;
    }
}
