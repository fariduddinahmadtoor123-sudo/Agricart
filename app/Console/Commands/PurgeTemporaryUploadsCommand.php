<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PurgeTemporaryUploadsCommand extends Command
{
    protected $signature = 'media:purge-temp
                            {--hours=24 : Delete Livewire temp files older than this many hours}
                            {--dry-run : List files without deleting}';

    protected $description = 'Delete stale Livewire temporary uploads (livewire-tmp)';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subHours($hours)->getTimestamp();

        $directories = array_values(array_filter([
            storage_path('app/private/livewire-tmp'),
            storage_path('app/livewire-tmp'),
            public_path('storage/livewire-tmp'),
            storage_path('app/public/livewire-tmp'),
        ], fn (string $path): bool => is_dir($path)));

        if ($directories === []) {
            $this->components->info('No livewire-tmp directories found.');

            return self::SUCCESS;
        }

        $deleted = 0;
        $bytes = 0;

        foreach ($directories as $directory) {
            foreach (File::files($directory) as $file) {
                if ($file->getMTime() > $cutoff) {
                    continue;
                }

                $size = $file->getSize();

                if ($dryRun) {
                    $this->line('[dry-run] '.$file->getPathname());
                    $deleted++;
                    $bytes += $size;

                    continue;
                }

                if (@unlink($file->getPathname())) {
                    $deleted++;
                    $bytes += $size;
                }
            }
        }

        $this->components->info(($dryRun ? 'Would delete' : 'Deleted')." {$deleted} temp file(s) (~".number_format($bytes / 1024 / 1024, 1).' MB).');

        return self::SUCCESS;
    }
}
