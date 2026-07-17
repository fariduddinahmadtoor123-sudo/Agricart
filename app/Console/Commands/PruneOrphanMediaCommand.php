<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneOrphanMediaCommand extends Command
{
    protected $signature = 'media:prune-orphans
                            {--dry-run : List orphan files without deleting}';

    protected $description = 'Delete unreferenced catalog/store image files left after re-uploads or optimization';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $disk = Storage::disk((string) config('media.disk', 'public'));
        $referenced = $this->referencedPaths();

        $directories = ['categories', 'brands', 'products', 'products/gallery', 'store'];
        $deleted = 0;
        $bytes = 0;

        foreach ($directories as $directory) {
            if (! $disk->exists($directory)) {
                continue;
            }

            foreach ($disk->allFiles($directory) as $path) {
                $normalized = str_replace('\\', '/', $path);

                if (isset($referenced[$normalized])) {
                    continue;
                }

                $size = $disk->size($normalized);

                if ($dryRun) {
                    $this->line('[dry-run] '.$normalized);
                } else {
                    $disk->delete($normalized);
                }

                $deleted++;
                $bytes += $size;
            }
        }

        $this->components->info(($dryRun ? 'Would delete' : 'Deleted')." {$deleted} orphan file(s) (~".number_format($bytes / 1024 / 1024, 1).' MB).');

        return self::SUCCESS;
    }

    /**
     * @return array<string, true>
     */
    private function referencedPaths(): array
    {
        $paths = [];

        foreach ([
            Category::query()->whereNotNull('image_path')->pluck('image_path'),
            Brand::query()->whereNotNull('image_path')->pluck('image_path'),
            Product::query()->whereNotNull('image_path')->pluck('image_path'),
            CompanyProfile::query()->whereNotNull('logo_path')->pluck('logo_path'),
            StoreSetting::query()->whereNotNull('logo_path')->pluck('logo_path'),
            StoreSetting::query()->whereNotNull('footer_logo_path')->pluck('footer_logo_path'),
            Customer::query()->whereNotNull('profile_photo_path')->pluck('profile_photo_path'),
            Supplier::query()->whereNotNull('profile_photo_path')->pluck('profile_photo_path'),
            User::query()->whereNotNull('profile_photo_path')->pluck('profile_photo_path'),
        ] as $collection) {
            foreach ($collection as $path) {
                if (is_string($path) && $path !== '') {
                    $paths[str_replace('\\', '/', $path)] = true;
                }
            }
        }

        foreach (Product::query()->whereNotNull('gallery_images')->cursor() as $product) {
            $gallery = $product->gallery_images;

            if (! is_array($gallery)) {
                continue;
            }

            foreach ($gallery as $path) {
                if (is_string($path) && $path !== '') {
                    $paths[str_replace('\\', '/', $path)] = true;
                }
            }
        }

        return $paths;
    }
}
