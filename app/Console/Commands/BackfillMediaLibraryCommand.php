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

class BackfillMediaLibraryCommand extends Command
{
    protected $signature = 'media:backfill-library';

    protected $description = 'Index existing public images and record every known usage';

    public function handle(MediaLibraryService $library): int
    {
        $indexed = 0;

        foreach ($this->singleImageSpecifications() as [$modelClass, $column, $collection, $source]) {
            $modelClass::query()
                ->whereNotNull($column)
                ->orderBy('id')
                ->chunkById(200, function ($owners) use ($library, $column, $collection, $source, &$indexed): void {
                    foreach ($owners as $owner) {
                        $path = $owner->{$column};

                        if (! is_string($path) || $path === '') {
                            continue;
                        }

                        $library->syncOwnerCollection(
                            $owner,
                            $collection,
                            [$path],
                            $this->ownerLabel($owner),
                            $source,
                        );
                        $indexed++;
                    }
                });
        }

        Product::query()
            ->whereNotNull('gallery_images')
            ->orderBy('id')
            ->chunkById(100, function ($products) use ($library, &$indexed): void {
                foreach ($products as $product) {
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
                }
            });

        $this->components->info("Indexed {$indexed} public media usage(s).");

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
            [Product::class, 'image_path', 'main', 'products'],
            [CompanyProfile::class, 'logo_path', 'logo', 'company'],
            [StoreSetting::class, 'logo_path', 'logo', 'store'],
            [StoreSetting::class, 'footer_logo_path', 'footer-logo', 'store'],
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
}
