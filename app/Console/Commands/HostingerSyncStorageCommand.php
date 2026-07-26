<?php

namespace App\Console\Commands;

use App\Services\Media\HostingerPublicStorageTarget;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class HostingerSyncStorageCommand extends Command
{
    protected $signature = 'hostinger:sync-storage
                            {--target= : Absolute path to public_html (overrides HOSTINGER_PUBLIC_HTML)}
                            {--dry-run : List copy/delete operations without changing files}
                            {--delete-stale : Delete target files absent from the approved public source}
                            {--replace-link : Replace a public/storage symlink with a real directory mirror (Hostinger Apache)}';

    protected $description = 'Mirror storage/app/public into the Apache-facing storage directory for fast static image serving';

    public function handle(HostingerPublicStorageTarget $targets): int
    {
        $publicHtml = $this->option('target') ?: config('hostinger.public_html');

        if (! is_string($publicHtml) || $publicHtml === '') {
            $this->components->error('Set HOSTINGER_PUBLIC_HTML in .env or pass --target=/path/to/public_html');

            return self::FAILURE;
        }

        $source = storage_path('app/public');
        $target = $targets->resolve($publicHtml);

        if ($target === null) {
            $this->components->error('Could not resolve a safe public storage mirror target.');

            return self::FAILURE;
        }

        if (! File::isDirectory($source)) {
            $this->components->error('Source storage/app/public missing.');

            return self::FAILURE;
        }

        if ($targets->isLaravelStorageTree($target, $source)) {
            $this->components->error('Refusing to mirror into Laravel storage root (would risk private files).');

            return self::FAILURE;
        }

        if ($targets->isExactLinkToSource($target, $source)) {
            $replace = (bool) $this->option('replace-link')
                || $targets->isDirectCheckout($publicHtml)
                || filled(config('hostinger.public_html'));

            if (! $replace) {
                $this->components->info('public/storage already links to storage/app/public; no mirror needed.');

                return self::SUCCESS;
            }

            if ($this->option('dry-run')) {
                $this->components->warn('Would replace storage symlink/junction with a real directory mirror for Apache.');
            } else {
                $this->components->warn('Replacing storage symlink/junction with a real directory mirror for Apache.');
                File::delete($target);

                if (File::exists($target) || is_link($target)) {
                    $this->components->error('Could not remove existing storage link: '.$target);

                    return self::FAILURE;
                }

                File::ensureDirectoryExists($target);
            }
        }

        if ($this->isLinkOrJunction($target) && ! $targets->isExactLinkToSource($target, $source)) {
            $this->components->error('public storage target is a link or junction that does not resolve to storage/app/public.');

            return self::FAILURE;
        }

        if (File::exists($target) && ! File::isDirectory($target) && ! is_link($target)) {
            $this->components->error('Public storage target exists and is not a directory.');

            return self::FAILURE;
        }

        if (! $this->option('dry-run') && ! File::isDirectory($target)) {
            File::ensureDirectoryExists($target);
        }

        if (File::isDirectory($target) && $this->containsDestinationLink($target)) {
            $this->components->error('Destination contains a nested symlink or junction; refusing to copy or delete through it.');

            return self::FAILURE;
        }

        $mode = $targets->isDirectCheckout($publicHtml) ? 'direct-checkout public/storage' : 'split public_html/storage';
        $this->components->info("Sync {$source} → {$target} ({$mode})");

        $copied = 0;
        $approved = [];

        foreach (File::allFiles($source) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());

            if ($this->isExcluded($relative)) {
                continue;
            }

            $approved[$relative] = true;
            $dest = $target.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

            if ($this->option('dry-run')) {
                $this->line("  copy storage/{$relative}");
                $copied++;

                continue;
            }

            if ($this->pathContainsLink($target, dirname($dest))) {
                $this->components->error("Destination path became a link or junction: storage/{$relative}");

                return self::FAILURE;
            }

            File::ensureDirectoryExists(dirname($dest));

            if (! File::copy($file->getPathname(), $dest)) {
                $this->components->error("Failed to copy: storage/{$relative}");

                return self::FAILURE;
            }

            $copied++;
        }

        $stale = 0;

        if (File::isDirectory($target)) {
            foreach (File::allFiles($target) as $file) {
                $relative = str_replace('\\', '/', $file->getRelativePathname());

                if (isset($approved[$relative]) || $this->isProtectedMirrorPath($relative)) {
                    continue;
                }

                $stale++;

                if ($this->option('dry-run')) {
                    $this->line("  stale storage/{$relative}".($this->option('delete-stale') ? ' (would delete)' : ''));
                } elseif ($this->option('delete-stale')) {
                    if ($this->pathContainsLink($target, $file->getPath())) {
                        $this->components->error("Refusing to delete through a destination link or junction: storage/{$relative}");

                        return self::FAILURE;
                    }
                    File::delete($file->getPathname());
                }
            }
        }

        $this->components->info("Done. {$copied} approved media file(s) synced; {$stale} stale/excluded target file(s) found.");

        if ($stale > 0 && ! $this->option('delete-stale')) {
            $this->components->warn('Review with --dry-run --delete-stale, then rerun with --delete-stale to remove stale files.');
        }

        return self::SUCCESS;
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

    private function isProtectedMirrorPath(string $relative): bool
    {
        foreach (['app/', 'framework/', 'logs/', 'debugbar/', 'pail/'] as $prefix) {
            if ($relative === rtrim($prefix, '/') || str_starts_with($relative, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function containsDestinationLink(string $target): bool
    {
        if (! File::isDirectory($target)) {
            return false;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            if ($this->isLinkOrJunction($entry->getPathname())) {
                return true;
            }
        }

        return false;
    }

    private function pathContainsLink(string $root, string $path): bool
    {
        $root = rtrim($root, DIRECTORY_SEPARATOR);
        $relative = ltrim(substr($path, strlen($root)), DIRECTORY_SEPARATOR);
        $current = $root;

        foreach (array_filter(explode(DIRECTORY_SEPARATOR, $relative)) as $segment) {
            $current .= DIRECTORY_SEPARATOR.$segment;

            if (File::exists($current) && $this->isLinkOrJunction($current)) {
                return true;
            }
        }

        return false;
    }

    private function isLinkOrJunction(string $path): bool
    {
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
