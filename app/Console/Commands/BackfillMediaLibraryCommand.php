<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\OnlineSaleOrderItem;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\PurchasePlanningItem;
use App\Models\PurchaseQuotationItem;
use App\Models\SaleItem;
use App\Models\SaleQuotationItem;
use App\Models\SaleReturnItem;
use App\Models\StoreSetting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Media\MediaLibraryService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class BackfillMediaLibraryCommand extends Command
{
    protected $signature = 'media:backfill-library';

    protected $description = 'Index existing images and record every known usage (products, categories, brands, profiles, …)';

    public function handle(MediaLibraryService $library): int
    {
        $indexed = 0;
        $failed = 0;

        foreach ($this->singleImageSpecifications() as [$modelClass, $column, $collection, $source]) {
            $modelClass::query()
                ->whereNotNull($column)
                ->orderBy('id')
                ->chunkById(200, function ($owners) use ($library, $column, $collection, $source, &$indexed, &$failed): void {
                    foreach ($owners as $owner) {
                        $path = $owner->{$column};

                        if (! is_string($path) || $path === '') {
                            continue;
                        }

                        try {
                            $usage = $library->attach(
                                $path,
                                $owner,
                                $collection,
                                0,
                                null,
                                $this->ownerLabel($owner).' — '.$this->collectionLabel($collection),
                                $source,
                            );

                            if ($usage !== null) {
                                $indexed++;
                            } else {
                                $failed++;
                            }
                        } catch (Throwable $exception) {
                            report($exception);
                            $failed++;
                        }
                    }
                });
        }

        Product::query()
            ->whereNotNull('gallery_images')
            ->orderBy('id')
            ->chunkById(100, function ($products) use ($library, &$indexed, &$failed): void {
                foreach ($products as $product) {
                    try {
                        $paths = is_array($product->gallery_images) ? $product->gallery_images : [];
                        $paths = array_values(array_filter($paths, fn (mixed $path): bool => is_string($path) && $path !== ''));
                        $library->syncOwnerCollection(
                            $product,
                            'gallery',
                            $paths,
                            $this->ownerLabel($product),
                            'products',
                        );
                        $indexed += count($paths);
                    } catch (Throwable $exception) {
                        report($exception);
                        $failed++;
                    }
                }
            });

        $library->forgetStatsCache();

        $this->components->info("Indexed {$indexed} media usage(s)".($failed > 0 ? "; {$failed} skipped/failed" : '').'.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{class-string<Model>, string, string, string}>
     */
    private function singleImageSpecifications(): array
    {
        return [
            [Category::class, 'image_path', 'main', 'categories'],
            [Brand::class, 'image_path', 'main', 'brands'],
            [Brand::class, 'image_original_path', 'original', 'brands'],
            [Product::class, 'image_path', 'main', 'products'],
            [Product::class, 'image_original_path', 'original', 'products'],
            [CompanyProfile::class, 'logo_path', 'logo', 'company'],
            [StoreSetting::class, 'logo_path', 'logo', 'store'],
            [StoreSetting::class, 'footer_logo_path', 'footer-logo', 'store'],
            [StoreSetting::class, 'product_watermark_logo_path', 'watermark-logo', 'store'],
            [StoreSetting::class, 'brand_watermark_logo_path', 'brand-watermark-logo', 'store'],
            [Customer::class, 'profile_photo_path', 'profile-photo', 'customers'],
            [Supplier::class, 'profile_photo_path', 'profile-photo', 'suppliers'],
            [User::class, 'profile_photo_path', 'profile-photo', 'users'],
            [PurchaseItem::class, 'image_path', 'snapshot', 'purchases'],
            [PurchasePlanningItem::class, 'image_path', 'snapshot', 'purchasing'],
            [PurchaseQuotationItem::class, 'image_path', 'snapshot', 'purchases'],
            [SaleItem::class, 'image_path', 'snapshot', 'sales'],
            [SaleQuotationItem::class, 'image_path', 'snapshot', 'sales'],
            [SaleReturnItem::class, 'image_path', 'snapshot', 'sales'],
            [OnlineSaleOrderItem::class, 'image_path', 'snapshot', 'online-orders'],
        ];
    }

    private function ownerLabel(Model $owner): string
    {
        foreach (['name_en', 'name', 'company_name', 'supplier_name', 'customer_name', 'product_name'] as $field) {
            $value = $owner->getAttribute($field);

            if (is_string($value) && filled($value)) {
                return trim($value);
            }
        }

        return Str::headline(class_basename($owner)).' #'.$owner->getKey();
    }

    private function collectionLabel(string $collection): string
    {
        return match ($collection) {
            'main' => 'Main image',
            'original' => 'Original image',
            'gallery' => 'Gallery',
            'logo' => 'Logo',
            'footer-logo' => 'Footer logo',
            'watermark-logo', 'brand-watermark-logo' => 'Watermark logo',
            'profile-photo' => 'Profile photo',
            'snapshot' => 'Historical image',
            default => Str::headline($collection),
        };
    }
}
