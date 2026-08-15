<?php

namespace App\Services\Store;

use App\Models\Category;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Services\SalesChannels\SalesChannelService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StoreProductService
{
    /** @var array<string, array{url: string, width: ?int, height: ?int}> */
    private array $imageMetadata = [];

    /** @var array<string, bool> */
    private array $publicPathExists = [];

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginatedProductsForCategory(Category $category, int $perPage = 36): LengthAwarePaginator
    {
        $products = Product::query()
            ->whereIn('status', ['active', 'inactive'])
            ->where(function ($query) use ($category): void {
                $query->where('category_id', $category->id)
                    ->orWhereIn('id', function ($sub) use ($category): void {
                        $sub->select('product_id')
                            ->from('product_display_category')
                            ->where('category_id', $category->id);
                    });
            })
            ->with(['brand', 'category:id,image_path'])
            ->orderBy('name_en')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        $this->primeImageMetadata($products->items());

        return $products;
    }

    /**
     * All storefront products, ordered alphabetically by name, paginated.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginatedAllProducts(int $perPage = 36): LengthAwarePaginator
    {
        $perPage = max(12, min(96, $perPage));

        $products = Product::query()
            ->whereIn('status', ['active', 'inactive'])
            ->with(['brand', 'category:id,image_path'])
            ->orderBy('name_en')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        $this->primeImageMetadata($products->items());

        return $products;
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

        $products = $this->storefrontSearchQuery($term)
            ->with(['brand', 'category:id,parent_id,image_path,name_en,name_ur,slug'])
            ->tap(fn (Builder $query) => $this->orderBySearchRelevance($query, $term))
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        $this->primeImageMetadata($products->items());

        return $products;
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

        return Product::query()
            ->select([
                'id',
                'slug',
                'name_en',
                'name_ur',
                'code',
                'sku',
                'label_code',
                'status',
                'image_path',
                'wholesale_price',
                'retail_price',
                'brand_id',
                'category_id',
            ])
            ->whereIn('status', ['active', 'inactive'])
            ->tap(fn (Builder $query) => $this->applyStorefrontPreviewSearch($query, $term))
            ->with([
                'brand:id,name_en,name_ur',
                'category:id,parent_id,image_path,name_en,name_ur,slug',
            ])
            ->tap(fn (Builder $query) => $this->orderBySearchRelevance($query, $term))
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    private function storefrontSearchQuery(string $term): Builder
    {
        return Product::query()
            ->whereIn('status', ['active', 'inactive'])
            ->tap(fn (Builder $query) => $this->applyStorefrontSearch($query, $term));
    }

    /**
     * Inactive products stay visible on the storefront as "Coming soon".
     */
    public function isComingSoon(Product $product): bool
    {
        return ($product->status ?? '') !== 'active';
    }

    public function findStorefrontBySlug(string $slug): ?Product
    {
        return Product::query()
            ->where('slug', $slug)
            ->whereIn('status', ['active', 'inactive'])
            ->with([
                'brand',
                'category',
                'unit',
                'packUnit',
                'attributes',
                'controls' => fn ($query) => $query->where('status', 'active'),
                'deliveryMethods' => fn ($query) => $query
                    ->where('delivery_methods.status', 'active')
                    ->orderBy('delivery_methods.sort_order')
                    ->orderBy('delivery_methods.name_en')
                    ->with('rule'),
            ])
            ->first();
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
        if ($product === null) {
            return null;
        }

        foreach ($this->candidateImagePaths($product) as $path) {
            if ($this->publicFileExists($path)) {
                return Storage::disk('public')->url($path);
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function candidateImagePaths(Product $product): array
    {
        $paths = [];

        if (filled($product->image_path)) {
            $paths[] = (string) $product->image_path;
        }

        $categoryPath = $product->relationLoaded('category')
            ? $product->category?->image_path
            : $product->category()->value('image_path');

        if (is_string($categoryPath) && $categoryPath !== '') {
            $paths[] = $categoryPath;
        }

        return array_values(array_unique($paths));
    }

    /**
     * @return array{url: string, width: ?int, height: ?int}|null
     */
    public function imageData(?Product $product): ?array
    {
        if ($product === null) {
            return null;
        }

        foreach ($this->candidateImagePaths($product) as $path) {
            if ($this->publicFileExists($path)) {
                return $this->imageDataForPath($path);
            }
        }

        return null;
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

        // Storefront cards use the online entry price (wholesale); retail is POS-facing.
        $amount = $product->wholesale_price;

        if ($amount === null || $amount === '') {
            $amount = $product->retail_price;
        }

        if ($amount === null || $amount === '') {
            return null;
        }

        return number_format((float) $amount, 0);
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
                'deliveryMethods' => fn ($query) => $query
                    ->where('delivery_methods.status', 'active')
                    ->orderBy('delivery_methods.sort_order')
                    ->orderBy('delivery_methods.name_en')
                    ->with('rule'),
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

    /**
     * @return list<array{url: string, width: ?int, height: ?int}>
     */
    public function allImageData(Product $product): array
    {
        $paths = array_values(array_filter([
            $product->image_path,
            ...($product->gallery_images ?? []),
        ], static fn (mixed $path): bool => is_string($path) && filled($path)));

        $missing = array_values(array_filter($paths, fn (string $path): bool => ! array_key_exists($path, $this->imageMetadata)));

        if ($missing !== []) {
            $assets = MediaAsset::query()
                ->where('disk', 'public')
                ->whereIn('path', $missing)
                ->get(['path', 'width', 'height'])
                ->keyBy('path');

            foreach ($missing as $path) {
                $asset = $assets->get($path);
                $this->imageMetadata[$path] = [
                    'url' => Storage::disk('public')->url($path),
                    'width' => $asset?->width,
                    'height' => $asset?->height,
                ];
            }
        }

        return collect($paths)
            ->map(fn (string $path): array => $this->imageMetadata[$path])
            ->unique('url')
            ->values()
            ->all();
    }

    /**
     * @return array{url: string, width: ?int, height: ?int}
     */
    private function imageDataForPath(string $path): array
    {
        if (! isset($this->imageMetadata[$path])) {
            $asset = MediaAsset::query()
                ->where('disk', 'public')
                ->where('path', $path)
                ->first(['width', 'height']);

            $this->imageMetadata[$path] = [
                'url' => Storage::disk('public')->url($path),
                'width' => $asset?->width,
                'height' => $asset?->height,
            ];
        }

        return $this->imageMetadata[$path];
    }

    /**
     * @param  iterable<int, Product>  $products
     */
    private function primeImageMetadata(iterable $products): void
    {
        $paths = collect($products)
            ->flatMap(static fn (Product $product): array => array_values(array_filter([
                $product->image_path,
                ...($product->gallery_images ?? []),
            ], static fn (mixed $path): bool => is_string($path) && filled($path))))
            ->unique()
            ->values();

        if ($paths->isEmpty()) {
            return;
        }

        $assets = MediaAsset::query()
            ->where('disk', 'public')
            ->whereIn('path', $paths)
            ->get(['path', 'width', 'height'])
            ->keyBy('path');

        foreach ($paths as $path) {
            $asset = $assets->get($path);
            $this->imageMetadata[$path] = [
                'url' => Storage::disk('public')->url($path),
                'width' => $asset?->width,
                'height' => $asset?->height,
            ];
        }
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

    /**
     * Autocomplete only: names, codes, and the prebuilt search_document index.
     * Full search still walks related brands/categories/attributes.
     */
    private function applyStorefrontPreviewSearch(Builder $query, string $term): void
    {
        $this->applyStorefrontSearch($query, $term, preview: true);
    }

    private function applyStorefrontSearch(Builder $query, string $term, bool $preview = false): void
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

            $query->where(function (Builder $inner) use ($token, $needle, $preview): void {
                $this->applyProductToken($inner, $needle, $token, $preview)
                    ->orWhere('code', $token)
                    ->orWhere('sku', $token)
                    ->orWhere('label_code', $token);
            });
        }

        if (! $matchedAnyToken) {
            $needle = '%' . $this->escapeLike($term) . '%';

            $query->where(function (Builder $inner) use ($term, $needle, $preview): void {
                $this->applyProductToken($inner, $needle, $term, $preview)
                    ->orWhere('code', $term)
                    ->orWhere('sku', $term);
            });
        }
    }

    private function applyProductToken(Builder $query, string $needle, string $token, bool $preview = false): Builder
    {
        $columns = $preview
            ? ['name_en', 'name_ur', 'code', 'sku', 'label_code', 'slug', 'search_terms']
            : [
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

        if (DB::getDriverName() === 'mysql') {
            $booleanToken = preg_replace('/[^\pL\pN_-]+/u', '', $token) ?? '';

            if (mb_strlen($booleanToken) >= 3) {
                $query->whereRaw(
                    'MATCH(search_document) AGAINST (? IN BOOLEAN MODE)',
                    ['+'.$booleanToken.'*'],
                );
            } else {
                $query->whereRaw("search_document LIKE ? ESCAPE '!'", [$needle]);
            }
        } else {
            $query->whereRaw("search_document LIKE ? ESCAPE '!'", [$needle]);

            foreach ($columns as $column) {
                $query->orWhereRaw("{$column} LIKE ? ESCAPE '!'", [$needle]);
            }
        }

        if ($preview) {
            foreach (['name_en', 'name_ur', 'code', 'sku', 'label_code', 'slug'] as $column) {
                $query->orWhereRaw("{$column} LIKE ? ESCAPE '!'", [$needle]);
            }

            return $query;
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

    public function publicFileExists(string $path): bool
    {
        return $this->publicPathExists[$path] ??= Storage::disk('public')->exists($path);
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

    private function orderBySearchRelevance(Builder $query, string $term): void
    {
        $normalized = mb_strtolower(trim($term));
        $prefix = $this->escapeLike($normalized).'%';

        $query->orderByRaw(
            'CASE
                WHEN LOWER(COALESCE(sku, \'\')) = ? OR LOWER(COALESCE(code, \'\')) = ? OR LOWER(COALESCE(label_code, \'\')) = ? THEN 0
                WHEN LOWER(COALESCE(sku, \'\')) LIKE ? ESCAPE \'!\' OR LOWER(COALESCE(code, \'\')) LIKE ? ESCAPE \'!\' OR LOWER(COALESCE(label_code, \'\')) LIKE ? ESCAPE \'!\' THEN 1
                WHEN LOWER(COALESCE(name_en, \'\')) = ? OR LOWER(COALESCE(name_ur, \'\')) = ? THEN 2
                WHEN LOWER(COALESCE(name_en, \'\')) LIKE ? ESCAPE \'!\' OR LOWER(COALESCE(name_ur, \'\')) LIKE ? ESCAPE \'!\' THEN 3
                ELSE 4 END',
            [$normalized, $normalized, $normalized, $prefix, $prefix, $prefix, $normalized, $normalized, $prefix, $prefix],
        );

        if (DB::getDriverName() === 'mysql') {
            $booleanTokens = collect($this->searchTokens($term))
                ->map(static fn (string $token): string => preg_replace('/[^\pL\pN_-]+/u', '', $token) ?? '')
                ->filter(static fn (string $token): bool => mb_strlen($token) >= 2)
                ->take(8)
                ->map(static fn (string $token): string => '+'.$token.'*')
                ->implode(' ');

            if ($booleanTokens !== '') {
                $query->orderByRaw(
                    'MATCH(search_document) AGAINST (? IN BOOLEAN MODE) DESC',
                    [$booleanTokens],
                );
            }
        }

        $query->orderBy('name_en');
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
