<?php

namespace App\Services\Store;

use App\Models\Category;
use App\Models\Product;
use App\Services\SalesChannels\SalesChannelService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class StoreProductService
{
    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginatedProductsForCategory(Category $category, int $perPage = 36): LengthAwarePaginator
    {
        return Product::query()
            ->where('status', 'active')
            ->where(function ($query) use ($category): void {
                $query->where('category_id', $category->id)
                    ->orWhereIn('id', function ($sub) use ($category): void {
                        $sub->select('product_id')
                            ->from('product_display_category')
                            ->where('category_id', $category->id);
                    });
            })
            ->with(['brand'])
            ->orderBy('name_en')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginatedSearch(string $term, int $perPage = 36): LengthAwarePaginator
    {
        $term = trim($term);

        if ($term === '') {
            return Product::query()
                ->whereRaw('1 = 0')
                ->paginate($perPage);
        }

        return Product::query()
            ->where('status', 'active')
            ->tap(fn (Builder $query) => $this->applyStorefrontSearch($query, $term))
            ->with(['brand'])
            ->orderBy('name_en')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function imageUrl(?Product $product): ?string
    {
        if ($product === null || ! filled($product->image_path)) {
            return null;
        }

        return Storage::disk('public')->url($product->image_path);
    }

    public function localizedName(Product $product): string
    {
        if (app()->getLocale() === 'ur' && filled($product->name_ur)) {
            return $product->name_ur;
        }

        return $product->name_en;
    }

    public function formattedRetailPrice(Product $product): ?string
    {
        if (! app(SalesChannelService::class)->onlineStoreShowsPrices()) {
            return null;
        }

        if ($product->retail_price === null || $product->retail_price === '') {
            return null;
        }

        return number_format((float) $product->retail_price, 0);
    }

    public function findActiveBySlug(string $slug): ?Product
    {
        return Product::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->with([
                'brand',
                'category',
                'unit',
                'packUnit',
                'attributes',
                'controls' => fn ($query) => $query->where('status', 'active'),
            ])
            ->first();
    }

    /**
     * @return list<string>
     */
    public function allImageUrls(Product $product): array
    {
        $urls = [];

        if ($main = $this->imageUrl($product)) {
            $urls[] = $main;
        }

        foreach ($product->gallery_images ?? [] as $path) {
            if (! is_string($path) || ! filled($path)) {
                continue;
            }

            $url = Storage::disk('public')->url($path);

            if (! in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    public function localizedField(Product $product, string $base): ?string
    {
        $localizedKey = $base . '_' . app()->getLocale();

        if (app()->getLocale() !== 'en' && filled($product->{$localizedKey})) {
            return $product->{$localizedKey};
        }

        $englishKey = $base . '_en';
        $value = $product->{$englishKey};

        return filled($value) ? $value : null;
    }

    private function applyStorefrontSearch(Builder $query, string $term): void
    {
        $compact = ! str_contains($term, ' ');

        if ($compact && mb_strlen($term) >= 2) {
            $prefix = $term . '%';

            $query->where(function (Builder $inner) use ($term, $prefix): void {
                $inner->where('code', $term)
                    ->orWhere('sku', $term)
                    ->orWhere('label_code', $term)
                    ->orWhere('slug', $term)
                    ->orWhere('code', 'like', $prefix)
                    ->orWhere('sku', 'like', $prefix)
                    ->orWhere('label_code', 'like', $prefix)
                    ->orWhere('name_en', 'like', $prefix)
                    ->orWhere('name_ur', 'like', $prefix)
                    ->orWhereHas('brand', function (Builder $brandQuery) use ($prefix): void {
                        $brandQuery->where('name_en', 'like', $prefix)
                            ->orWhere('name_ur', 'like', $prefix);
                    });
            });

            return;
        }

        $needle = '%' . $term . '%';

        $query->where(function (Builder $inner) use ($needle): void {
            $inner->where('name_en', 'like', $needle)
                ->orWhere('name_ur', 'like', $needle)
                ->orWhere('code', 'like', $needle)
                ->orWhere('sku', 'like', $needle)
                ->orWhere('label_code', 'like', $needle)
                ->orWhere('slug', 'like', $needle)
                ->orWhereHas('brand', function (Builder $brandQuery) use ($needle): void {
                    $brandQuery->where('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle);
                });
        });
    }
}
