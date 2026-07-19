<?php

namespace App\Console\Commands;

use App\Services\Media\MediaLibraryService;
use Illuminate\Console\Command;

class PruneMediaTrashCommand extends Command
{
    protected $signature = 'media:prune-trash {--days= : Trash retention in days}';

    protected $description = 'Permanently remove unused media that has remained in trash';

    public function handle(MediaLibraryService $library): int
    {
        $days = max(1, (int) ($this->option('days') ?: config('retention.media_trash_days')));
        $count = $library->pruneTrashed($days);
        $this->components->info("Permanently deleted {$count} expired media item(s).");

        return self::SUCCESS;
    }
}
