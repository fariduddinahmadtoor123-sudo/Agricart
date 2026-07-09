<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class HostingerSyncStorageCommand extends Command
{
    protected $signature = 'hostinger:sync-storage
                            {--target= : Absolute path to public_html (overrides HOSTINGER_PUBLIC_HTML)}
                            {--dry-run : List files that would be copied}';

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

        if (! $this->option('dry-run') && ! File::isDirectory($target)) {
            File::ensureDirectoryExists($target);
        }

        $this->components->info("Sync {$source} → {$target}");

        $copied = 0;

        foreach (File::allFiles($source) as $file) {
            $relative = $file->getRelativePathname();
            $dest = $target.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

            if ($this->option('dry-run')) {
                $this->line("  copy storage/{$relative}");
                $copied++;

                continue;
            }

            File::ensureDirectoryExists(dirname($dest));

            if (! File::copy($file->getPathname(), $dest)) {
                $this->components->error("Failed to copy: storage/{$relative}");

                return self::FAILURE;
            }

            $copied++;
        }

        $this->components->info("Done. {$copied} media file(s) synced to public_html/storage.");
        $this->components->warn('Run after new uploads in admin; Apache will serve these directly (faster than PHP).');

        return self::SUCCESS;
    }
}
