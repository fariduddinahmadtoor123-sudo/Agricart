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
use App\Services\Media\OptimizedImageStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeStoredImagesCommand extends Command
{
    protected $signature = 'media:optimize-images
                            {--dry-run : Show what would change without writing files}
                            {--limit=0 : Max records to process (0 = all)}';

    protected $description = 'Resize and convert existing catalog/store images to WebP for faster delivery';

    public function handle(OptimizedImageStorage $images): int
    {
        if (! function_exists('imagewebp')) {
            $this->components->error('PHP GD WebP support is required.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $processed = 0;
        $savedBytes = 0;

        $jobs = [
            [Category::class, 'image_path', 'catalog'],
            [Brand::class, 'image_path', 'catalog'],
            [Product::class, 'image_path', 'catalog'],
            [CompanyProfile::class, 'logo_path', 'logo'],
            [StoreSetting::class, 'logo_path', 'logo'],
            [StoreSetting::class, 'footer_logo_path', 'logo'],
            [Customer::class, 'profile_photo_path', 'avatar'],
            [Supplier::class, 'profile_photo_path', 'avatar'],
            [User::class, 'profile_photo_path', 'avatar'],
        ];

        foreach ($jobs as [$modelClass, $field, $preset]) {
            $query = $modelClass::query()->whereNotNull($field)->where($field, '!=', '');

            foreach ($query->cursor() as $model) {
                if ($limit > 0 && $processed >= $limit) {
                    break 2;
                }

                $oldPath = (string) $model->{$field};

                if ($oldPath === '' || str_ends_with(strtolower($oldPath), '.webp')) {
                    continue;
                }

                $disk = Storage::disk((string) config('media.disk', 'public'));

                if (! $disk->exists($oldPath)) {
                    continue;
                }

                $before = $disk->size($oldPath);

                if ($dryRun) {
                    $this->line("[dry-run] {$modelClass}#{$model->getKey()} {$field}: {$oldPath}");
                    $processed++;

                    continue;
                }

                $newPath = $images->optimizeStoredPath($oldPath, $preset);

                if ($newPath === null || $newPath === $oldPath) {
                    continue;
                }

                $model->forceFill([$field => $newPath])->save();
                $after = $disk->exists($newPath) ? $disk->size($newPath) : $before;
                $savedBytes += max(0, $before - $after);
                $processed++;
                $this->line("Optimized {$oldPath} → {$newPath}");
            }
        }

        if (! $dryRun) {
            $productQuery = Product::query()->whereNotNull('gallery_images');

            foreach ($productQuery->cursor() as $product) {
                if ($limit > 0 && $processed >= $limit) {
                    break;
                }

                $gallery = $product->gallery_images;

                if (! is_array($gallery) || $gallery === []) {
                    continue;
                }

                $changed = false;
                $disk = Storage::disk((string) config('media.disk', 'public'));

                foreach ($gallery as $index => $path) {
                    if (! is_string($path) || $path === '' || str_ends_with(strtolower($path), '.webp')) {
                        continue;
                    }

                    if (! $disk->exists($path)) {
                        continue;
                    }

                    $before = $disk->size($path);
                    $newPath = $images->optimizeStoredPath($path, 'gallery');

                    if ($newPath === null || $newPath === $path) {
                        continue;
                    }

                    $gallery[$index] = $newPath;
                    $changed = true;
                    $after = $disk->exists($newPath) ? $disk->size($newPath) : $before;
                    $savedBytes += max(0, $before - $after);
                    $processed++;
                    $this->line("Optimized gallery {$path} → {$newPath}");
                }

                if ($changed) {
                    $product->forceFill(['gallery_images' => array_values($gallery)])->save();
                }
            }
        }

        $this->components->info($dryRun
            ? "Dry run complete — {$processed} image(s) would be optimized."
            : "Optimized {$processed} image(s); saved ~".number_format($savedBytes / 1024, 1).' KB.');

        return self::SUCCESS;
    }
}
