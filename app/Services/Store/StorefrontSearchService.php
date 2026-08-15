<?php

namespace App\Services\Store;

use App\Models\Category;
use App\Models\StorePage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorefrontSearchService
{
    public function __construct(
        private readonly StoreProductService $products,
        private readonly StoreCategoryService $categoryService,
    ) {}

    /**
     * @return array{
     *     products: LengthAwarePaginator,
     *     categories: Collection<int, array<string, mixed>>,
     *     pages: Collection<int, array<string, mixed>>
     * }
     */
    public function search(string $term, int $productsPerPage = 36): array
    {
        $term = trim($term);

        return [
            'products' => $this->products->paginatedSearch($term, $productsPerPage),
            'categories' => $this->categories($term, 100),
            'pages' => $this->pages($term, 20),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function preview(string $term, int $limit = 25): Collection
    {
        $term = trim($term);
        $limit = max(1, min(25, $limit));

        if (! $this->products->isSearchableTerm($term)) {
            return collect();
        }

        $categoryResults = $this->categories($term, min(12, $limit), preview: true);
        $pageResults = $this->pages($term, min(5, $limit), preview: true);
        $remaining = max(0, $limit - $categoryResults->count() - $pageResults->count());
        $productResults = $this->products->searchPreview($term, max(1, $remaining));
        $productCategories = $this->categoryService->ancestorMap(
            $productResults->map(static fn ($product) => $product->category)->all(),
        );

        $productResults = $productResults
            ->map(function ($product) use ($productCategories): array {
                $brand = $this->products->localizedBrandName($product);
                $hierarchy = $this->categoryService->compactHierarchyLabel(
                    $this->categoryService->hierarchyNames($product->category, $productCategories),
                );

                return [
                    'id' => $product->id,
                    'type' => 'product',
                    'type_label' => __('agricart.storefront.search.products'),
                    'name' => $this->products->localizedName($product),
                    'url' => route('store.product', $product->slug),
                    'image' => $this->products->imageUrl($product),
                    'price' => ($amount = $this->products->formattedRetailPrice($product))
                        ? __('agricart.storefront.products.price', ['amount' => $amount])
                        : null,
                    'code' => filled($product->code) ? $product->code : null,
                    'brand' => $brand,
                    'hierarchy' => $hierarchy,
                ];
            });

        return $categoryResults
            ->concat($productResults)
            ->concat($pageResults)
            ->take($limit)
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function categories(string $term, int $limit, bool $preview = false): Collection
    {
        if (! $this->products->isSearchableTerm($term)) {
            return collect();
        }

        $fields = $preview
            ? ['name_en', 'name_ur', 'code', 'slug', 'search_terms']
            : [
                'name_en', 'name_ur', 'code', 'slug',
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
            ];

        $query = Category::query()
            ->where('status', 'active')
            ->with('parent:id,parent_id,name_en,name_ur,image_path,slug')
            ->where(fn (Builder $query) => $this->applyTokenSearch($query, $term, $fields));

        if ($preview) {
            $normalized = mb_strtolower(trim($term));
            $prefix = $this->escapeLike($normalized).'%';

            $query->select([
                'id', 'parent_id', 'slug', 'name_en', 'name_ur', 'code', 'image_path',
                'short_description_en', 'short_description_ur',
            ])
                ->orderByRaw(
                    'CASE
                        WHEN LOWER(COALESCE(name_en, \'\')) = ? OR LOWER(COALESCE(name_ur, \'\')) = ? THEN 0
                        WHEN LOWER(COALESCE(name_en, \'\')) LIKE ? ESCAPE \'!\' OR LOWER(COALESCE(name_ur, \'\')) LIKE ? ESCAPE \'!\' THEN 1
                        ELSE 2 END',
                    [$normalized, $normalized, $prefix, $prefix],
                )
                ->limit($limit);
        } else {
            $query->limit(max(200, $limit));
        }

        return $query
            ->get()
            ->sortBy(fn (Category $category): int => $this->relevance(
                $term,
                $category->name_en,
                $category->name_ur,
                $category->code,
            ))
            ->take($limit)
            ->values()
            ->pipe(function (Collection $matches): Collection {
                $byId = $this->categoryService->ancestorMap($matches);

                return $matches->map(function (Category $category) use ($byId): array {
                    $names = $this->categoryService->hierarchyNames($category, $byId);
                    $hierarchy = $this->categoryService->compactHierarchyLabel($names);
                    $isNested = count($names) > 1;

                    return [
                        'id' => $category->id,
                        'type' => 'category',
                        'type_label' => $isNested
                            ? __('agricart.storefront.search.subcategory')
                            : __('agricart.storefront.search.category'),
                        'name' => $this->localized($category->name_en, $category->name_ur),
                        'url' => route('store.category', $category->slug),
                        'image' => $this->categoryImageUrl($category),
                        'meta' => $hierarchy,
                        'hierarchy' => $hierarchy,
                        'excerpt' => $this->excerpt(
                            $this->localized($category->short_description_en, $category->short_description_ur)
                                ?: $this->localized($category->full_description_en, $category->full_description_ur),
                        ),
                    ];
                })->values();
            });
    }

    private function categoryImageUrl(Category $category): ?string
    {
        foreach ([$category->image_path, $category->parent?->image_path] as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            if (! $this->products->publicFileExists($path)) {
                continue;
            }

            return Storage::disk('public')->url($path);
        }

        return null;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function pages(string $term, int $limit, bool $preview = false): Collection
    {
        if (! $this->products->isSearchableTerm($term)) {
            return collect();
        }

        $fields = $preview
            ? ['title_en', 'title_ur', 'slug']
            : ['title_en', 'title_ur', 'content_en', 'content_ur', 'slug'];

        return StorePage::query()
            ->where('is_active', true)
            ->where(fn (Builder $query) => $this->applyTokenSearch($query, $term, $fields))
            ->limit($preview ? $limit : 30)
            ->get()
            ->sortBy(fn (StorePage $page): int => $this->relevance(
                $term,
                $page->title_en,
                $page->title_ur,
                $page->slug,
            ))
            ->take($limit)
            ->map(fn (StorePage $page): array => [
                'id' => $page->id,
                'type' => 'page',
                'type_label' => __('agricart.storefront.search.page'),
                'name' => $page->localizedTitle(),
                'url' => $page->publicUrl(),
                'image' => null,
                'meta' => null,
                'excerpt' => $this->excerpt($page->localizedContent()),
            ])
            ->values();
    }

    /**
     * @param  list<string>  $fields
     */
    private function applyTokenSearch(Builder $query, string $term, array $fields): void
    {
        $tokens = preg_split('/\s+/u', trim($term), -1, PREG_SPLIT_NO_EMPTY);
        $tokens = is_array($tokens) ? array_slice(array_values(array_filter(
            $tokens,
            static fn (string $token): bool => mb_strlen($token) >= 2,
        )), 0, 8) : [];

        foreach ($tokens === [] ? [$term] : $tokens as $token) {
            $needle = '%'.$this->escapeLike($token).'%';

            $query->where(function (Builder $inner) use ($fields, $needle): void {
                foreach ($fields as $index => $field) {
                    $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                    $inner->{$method}("{$field} LIKE ? ESCAPE '!'", [$needle]);
                }
            });
        }
    }

    private function relevance(string $term, ?string ...$values): int
    {
        $needle = mb_strtolower(trim($term));
        $score = 100;

        foreach ($values as $value) {
            $value = mb_strtolower(trim((string) $value));

            if ($value === $needle) {
                return 0;
            }

            if ($value !== '' && str_starts_with($value, $needle)) {
                $score = min($score, 10);
            } elseif ($value !== '' && str_contains($value, $needle)) {
                $score = min($score, 20);
            }
        }

        return $score;
    }

    private function localized(?string $english, ?string $urdu): string
    {
        if (app()->getLocale() === 'ur' && filled($urdu)) {
            return (string) $urdu;
        }

        return (string) ($english ?: $urdu);
    }

    private function excerpt(?string $content): ?string
    {
        if (! filled($content)) {
            return null;
        }

        $plain = html_entity_decode(strip_tags((string) $content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/\s+/u', ' ', trim($plain));

        return filled($plain) ? Str::limit((string) $plain, 150) : null;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
