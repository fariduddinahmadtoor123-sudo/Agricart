<?php

namespace App\Services\Store;

use App\Models\Category;
use App\Models\Product;
use App\Services\SalesChannels\SalesChannelService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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

        if ($term === '' || ! $this->isSearchableTerm($term)) {
            return Product::query()
                ->whereRaw('1 = 0')
                ->paginate($perPage);
        }

        return $this->storefrontSearchQuery($term)
            ->with(['brand'])
            ->orderBy('name_en')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return EloquentCollection<int, Product>
     */
    public function searchPreview(string $term, int $limit = 25): EloquentCollection
    {
        $term = trim($term);
        $limit = max(1, min(25, $limit));

        if (! $this->isSearchableTerm($term)) {
            return new EloquentCollection;
        }

        return $this->storefrontSearchQuery($term)
            ->with(['brand'])
            ->orderBy('name_en')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    private function storefrontSearchQuery(string $term): Builder
    {
        return Product::query()
            ->where('status', 'active')
            ->tap(fn (Builder $query) => $this->applyStorefrontSearch($query, $term));
    }

    private function isSearchableTerm(string $term): bool
    {
        $term = trim($term);

        if ($term === '') {
            return false;
        }

        $tokens = $this->searchTokens($term);

        if ($tokens === []) {
            return mb_strlen($term) >= 2;
        }

        foreach ($tokens as $token) {
            if (mb_strlen($token) >= 2) {
                return true;
            }
        }

        return mb_strlen($term) >= 2;
    }

    /**
     * @return list<string>
     */
    private function searchTokens(string $term): array
    {
        $parts = preg_split('/\s+/u', trim($term), -1, PREG_SPLIT_NO_EMPTY);

        return is_array($parts) ? array_values($parts) : [];
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

    public function localizedBrandName(Product $product): ?string
    {
        $brand = $product->brand;

        if ($brand === null) {
            return null;
        }

        if (app()->getLocale() === 'ur' && filled($brand->name_ur)) {
            return $brand->name_ur;
        }

        return $brand->name_en;
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
        $term = trim($term);

        if ($term === '') {
            $query->whereRaw('1 = 0');

            return;
        }

        $tokens = $this->searchTokens($term);
        $matchedAnyToken = false;

        foreach ($tokens as $token) {
            if (mb_strlen($token) < 2) {
                continue;
            }

            $matchedAnyToken = true;
            $needle = '%' . $this->escapeLike($token) . '%';

            $query->where(function (Builder $inner) use ($token, $needle): void {
                $inner->where('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle)
                    ->orWhere('code', 'like', $needle)
                    ->orWhere('sku', 'like', $needle)
                    ->orWhere('label_code', 'like', $needle)
                    ->orWhere('slug', 'like', $needle)
                    ->orWhere('code', $token)
                    ->orWhere('sku', $token)
                    ->orWhere('label_code', $token)
                    ->orWhereHas('brand', function (Builder $brandQuery) use ($needle): void {
                        $brandQuery->where('name_en', 'like', $needle)
                            ->orWhere('name_ur', 'like', $needle);
                    });
            });
        }

        if (! $matchedAnyToken) {
            $needle = '%' . $this->escapeLike($term) . '%';

            $query->where(function (Builder $inner) use ($term, $needle): void {
                $inner->where('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle)
                    ->orWhere('code', 'like', $needle)
                    ->orWhere('sku', 'like', $needle)
                    ->orWhere('label_code', 'like', $needle)
                    ->orWhere('slug', 'like', $needle)
                    ->orWhere('code', $term)
                    ->orWhere('sku', $term)
                    ->orWhereHas('brand', function (Builder $brandQuery) use ($needle): void {
                        $brandQuery->where('name_en', 'like', $needle)
                            ->orWhere('name_ur', 'like', $needle);
                    });
            });
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
