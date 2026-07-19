<?php

namespace App\Console\Commands;

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
                            {--delete-stale : Delete target files absent from the approved public source}';

    protected $description = 'Mirror storage/app/public into public_html/storage for fast static image serving on shared hosting';

    public function handle(): int
    {
        $publicHtml = $this->option('target') ?: config('hostinger.public_html');

        if (! is_string($publicHtml) || $publicHtml === '') {
            $this->components->error('Set HOSTINGER_PUBLIC_HTML in .env or pass --target=/path/to/public_html');

            return self::FAILURE;
        }

        $publicHtml = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $publicHtml), DIRECTORY_SEPARATOR);
        $source = storage_path('app/public');
        $target = $publicHtml.DIRECTORY_SEPARATOR.'storage';

        if (! File::isDirectory($source)) {
            $this->components->error('Source storage/app/public missing.');

            return self::FAILURE;
        }

        $resolvedSource = realpath($source);
        $resolvedTarget = realpath($target);

        if (is_string($resolvedSource) && is_string($resolvedTarget) && strcasecmp($resolvedSource, $resolvedTarget) === 0) {
            $this->components->info('Verified public_html/storage link points to storage/app/public; no mirror is needed.');

            return self::SUCCESS;
        }

        if ($this->isLinkOrJunction($target)) {
            $this->components->error('public_html/storage is a link or junction that does not resolve exactly to storage/app/public.');

            return self::FAILURE;
        }

        if (File::exists($target) && ! File::isDirectory($target)) {
            $this->components->error('public_html/storage exists and is not a directory.');

            return self::FAILURE;
        }

        if (! $this->option('dry-run') && ! File::isDirectory($target)) {
            File::ensureDirectoryExists($target);
        }

        if (File::isDirectory($target) && $this->containsDestinationLink($target)) {
            $this->components->error('Destination contains a symlink or junction; refusing to copy or delete through it.');

            return self::FAILURE;
        }

        $this->components->info("Sync {$source} → {$target}");

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

                if (isset($approved[$relative])) {
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

    private function containsDestinationLink(string $target): bool
    {
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
