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

    public function isSearchableTerm(string $term): bool
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

        return is_array($parts) ? array_slice(array_values($parts), 0, 8) : [];
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
                $this->applyProductToken($inner, $needle)
                    ->orWhere('code', $token)
                    ->orWhere('sku', $token)
                    ->orWhere('label_code', $token);
            });
        }

        if (! $matchedAnyToken) {
            $needle = '%' . $this->escapeLike($term) . '%';

            $query->where(function (Builder $inner) use ($term, $needle): void {
                $this->applyProductToken($inner, $needle)
                    ->orWhere('code', $term)
                    ->orWhere('sku', $term);
            });
        }
    }

    private function applyProductToken(Builder $query, string $needle): Builder
    {
        $columns = [
            'name_en', 'name_ur', 'code', 'sku', 'label_code', 'slug',
            'short_description_en', 'short_description_ur',
            'full_description_en', 'full_description_ur',
            'usage_guidelines_en', 'usage_guidelines_ur',
            'benefits_en', 'benefits_ur',
            'warnings_en', 'warnings_ur',
            'buying_guide_en', 'buying_guide_ur',
            'seo_title_en', 'seo_title_ur',
            'seo_description_en', 'seo_description_ur',
            'seo_keywords_en', 'seo_keywords_ur',
            'seo_focus_keyword_en', 'seo_focus_keyword_ur',
            'search_terms', 'hs_code',
            'customs_notes_en', 'customs_notes_ur',
            'import_notes_en', 'import_notes_ur',
            'export_notes_en', 'export_notes_ur',
        ];

        foreach ($columns as $index => $column) {
            $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
            $query->{$method}("{$column} LIKE ? ESCAPE '!'", [$needle]);
        }

        return $query
            ->orWhereHas('brand', fn (Builder $related) => $this->applyRelatedToken($related, $needle))
            ->orWhereHas('category', fn (Builder $related) => $this->applyRelatedToken($related, $needle, true))
            ->orWhereHas('displayCategories', fn (Builder $related) => $this->applyRelatedToken($related, $needle, true))
            ->orWhereHas('unit', fn (Builder $related) => $this->applyRelatedToken($related, $needle))
            ->orWhereHas('packUnit', fn (Builder $related) => $this->applyRelatedToken($related, $needle))
            ->orWhereHas('attributes', function (Builder $related) use ($needle): void {
                $this->applyRelatedToken($related, $needle)
                    ->orWhereRaw("product_attribute.value LIKE ? ESCAPE '!'", [$needle]);
            })
            ->orWhereHas('controls', function (Builder $related) use ($needle): void {
                $this->applyRelatedToken($related, $needle)
                    ->orWhereRaw("value_en LIKE ? ESCAPE '!'", [$needle])
                    ->orWhereRaw("value_ur LIKE ? ESCAPE '!'", [$needle]);
            });
    }

    private function applyRelatedToken(Builder $query, string $needle, bool $includeSearchTerms = false): Builder
    {
        if ($includeSearchTerms) {
            $query->where('status', 'active');
        }

        $query->where(function (Builder $related) use ($needle, $includeSearchTerms): void {
            $related->whereRaw("name_en LIKE ? ESCAPE '!'", [$needle])
                ->orWhereRaw("name_ur LIKE ? ESCAPE '!'", [$needle])
                ->orWhereRaw("code LIKE ? ESCAPE '!'", [$needle])
                ->orWhereRaw("slug LIKE ? ESCAPE '!'", [$needle]);

            if ($includeSearchTerms) {
                $related->orWhereRaw("search_terms LIKE ? ESCAPE '!'", [$needle])
                    ->orWhereRaw("seo_keywords_en LIKE ? ESCAPE '!'", [$needle])
                    ->orWhereRaw("seo_keywords_ur LIKE ? ESCAPE '!'", [$needle]);
            }
        });

        return $query;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
