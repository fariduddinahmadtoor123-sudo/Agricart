<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class HostingerSyncPublicCommand extends Command
{
    protected $signature = 'hostinger:sync-public
                            {--target= : Absolute path to public_html (overrides HOSTINGER_PUBLIC_HTML)}
                            {--clean : Remove stale/duplicate build directories before publishing}
                            {--dry-run : List files that would be copied}';

    protected $description = 'Copy public/ web assets into Hostinger public_html (fixed document root)';

    /** @var list<string> */
    protected array $skipNames = [
        'storage',
        'laravel-root.php',
        '.gitignore',
    ];

    public function handle(): int
    {
        $target = $this->option('target') ?: config('hostinger.public_html');

        if (! is_string($target) || $target === '') {
            $this->components->error('Set HOSTINGER_PUBLIC_HTML in .env or pass --target=/path/to/public_html');

            return self::FAILURE;
        }

        $target = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $target), DIRECTORY_SEPARATOR);
        $source = public_path();

        if (! File::isDirectory($source)) {
            $this->components->error('Source public/ directory missing.');

            return self::FAILURE;
        }

        $directCheckout = $this->samePath(dirname($source), $target);

        if ($directCheckout) {
            return $this->handleDirectCheckout($source, $target);
        }

        if (! $this->option('dry-run') && ! File::isDirectory($target)) {
            if (! @mkdir($target, 0755, true) && ! File::isDirectory($target)) {
                $this->components->error("Cannot create target directory: {$target}");

                return self::FAILURE;
            }
        }

        if ($this->option('clean')) {
            foreach ([
                $target.DIRECTORY_SEPARATOR.'build',
                $target.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'build',
            ] as $staleBuild) {
                if ($this->samePath($staleBuild, $source.DIRECTORY_SEPARATOR.'build')) {
                    continue;
                }

                if (File::isDirectory($staleBuild)) {
                    if ($this->option('dry-run')) {
                        $this->line("  remove {$staleBuild}");
                    } else {
                        File::deleteDirectory($staleBuild);
                    }
                }
            }
        }

        $this->components->info("Sync {$source} → {$target}");

        $copied = 0;
        $skipped = 0;

        foreach ($this->publicFiles($source) as $file) {
            $relative = $file->getRelativePathname();

            if ($this->shouldSkip($relative)) {
                $skipped++;

                continue;
            }

            $dest = $target.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

            if ($this->option('dry-run')) {
                $this->line("  copy {$relative}");
                $copied++;

                continue;
            }

            File::ensureDirectoryExists(dirname($dest));

            if (! File::copy($file->getPathname(), $dest)) {
                $this->components->error("Failed to copy: {$relative}");

                return self::FAILURE;
            }

            $copied++;
        }

        $this->components->info("Done. {$copied} file(s) synced, {$skipped} skipped (e.g. public/storage).");

        if (! $this->option('dry-run') && ! File::exists($target.DIRECTORY_SEPARATOR.'.htaccess')) {
            $this->components->error('public_html/.htaccess is missing after sync — source public/.htaccess must exist.');

            return self::FAILURE;
        }

        if (! File::exists($target.'/laravel-root.php') && ! File::exists($source.'/laravel-root.php')) {
            $this->components->warn('If the site shows "application root not found", copy public/laravel-root.php.example to public_html/laravel-root.php with your app path.');
        }

        if (! File::exists($target.'/build/manifest.json')) {
            $this->components->warn('Vite manifest missing — run npm run build in the app root before syncing.');
        }

        return self::SUCCESS;
    }

    protected function handleDirectCheckout(string $source, string $target): int
    {
        $this->components->warn(
            'The Git checkout is inside public_html. Using root .htaccess to map all requests to public/.'
        );

        $rootMapper = $target.DIRECTORY_SEPARATOR.'.htaccess';

        if (! File::exists($rootMapper)) {
            $this->components->error('Root .htaccess is missing; private Laravel files could be exposed.');

            return self::FAILURE;
        }

        $duplicateBuild = $target.DIRECTORY_SEPARATOR.'build';

        if ($this->option('clean') && File::isDirectory($duplicateBuild)) {
            if ($this->option('dry-run')) {
                $this->line("  remove {$duplicateBuild}");
            } else {
                File::deleteDirectory($duplicateBuild);
            }
        }

        if (! File::exists($source.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'manifest.json')) {
            $this->components->error('Vite manifest missing from public/build — run npm run build.');

            return self::FAILURE;
        }

        $this->components->info('Direct checkout ready: /build is served from public/build.');

        return self::SUCCESS;
    }

    /**
     * All files under public/, including dotfiles (.htaccess).
     *
     * @return iterable<int, SplFileInfo>
     */
    protected function publicFiles(string $source): iterable
    {
        return Finder::create()
            ->files()
            ->ignoreDotFiles(false)
            ->ignoreVCS(true)
            ->in($source);
    }

    protected function shouldSkip(string $relative): bool
    {
        $normalized = str_replace('\\', '/', $relative);

        foreach ($this->skipNames as $skip) {
            if ($normalized === $skip || str_starts_with($normalized, $skip.'/')) {
                return true;
            }
        }

        return false;
    }

    protected function samePath(string $first, string $second): bool
    {
        $firstReal = realpath($first);
        $secondReal = realpath($second);

        if ($firstReal === false || $secondReal === false) {
            return false;
        }

        return rtrim($firstReal, DIRECTORY_SEPARATOR) === rtrim($secondReal, DIRECTORY_SEPARATOR);
    }
}
