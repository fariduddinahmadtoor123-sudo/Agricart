<?php

namespace App\Console\Commands;

use App\Services\Catalog\BrandImageWatermarkService;
use Illuminate\Console\Command;
use Throwable;

class ApplyBrandWatermarksCommand extends Command
{
    protected $signature = 'brands:apply-watermarks {--force : Regenerate even when already at current watermark version}';

    protected $description = 'Copy brand logos to private originals (if needed) and regenerate public watermarked logos';

    public function handle(BrandImageWatermarkService $watermarks): int
    {
        try {
            $stats = $watermarks->applyToAll((bool) $this->option('force'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Brand watermarks applied. processed=%d skipped=%d failed=%d',
            $stats['processed'],
            $stats['skipped'],
            $stats['failed'],
        ));

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
