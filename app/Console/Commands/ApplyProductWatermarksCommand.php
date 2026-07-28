<?php

namespace App\Console\Commands;

use App\Services\Catalog\ProductImageWatermarkService;
use Illuminate\Console\Command;
use Throwable;

class ApplyProductWatermarksCommand extends Command
{
    protected $signature = 'products:apply-watermarks {--force : Regenerate even when already at current watermark version}';

    protected $description = 'Copy product images to private originals (if needed) and regenerate public watermarked storefront images';

    public function handle(ProductImageWatermarkService $watermarks): int
    {
        try {
            $stats = $watermarks->applyToAll((bool) $this->option('force'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Watermarks applied. processed=%d skipped=%d failed=%d',
            $stats['processed'],
            $stats['skipped'],
            $stats['failed'],
        ));

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
