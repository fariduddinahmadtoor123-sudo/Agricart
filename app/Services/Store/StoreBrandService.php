<?php

namespace App\Services\Store;

use App\Models\Brand;
use App\Support\Catalog\BrandAgricartReviewCriteria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class StoreBrandService
{
    public const LIST_PER_PAGE = 500;

    /**
     * Active brands for the footer marquee (logo preferred, then name).
     *
     * @return Collection<int, Brand>
     */
    public function carouselBrands(int $limit = 24): Collection
    {
        return $this->publicBrandQuery()
            ->orderByDesc('agricart_rating')
            ->orderBy('name_en')
            ->limit($limit)
            ->get();
    }

    /**
     * @return LengthAwarePaginator<int, Brand>
     */
    public function listedBrands(?string $search = null, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage = max(1, $perPage ?? self::LIST_PER_PAGE);
        $query = $this->publicBrandQuery();

        if ($this->isSearchableTerm((string) $search)) {
            $term = trim((string) $search);
            $this->applyBrandSearch($query, $term);
            $this->orderByBrandSearchRelevance($query, $term);
        }

        return $query
            ->orderBy('name_en')
            ->paginate($perPage)
            ->withQueryString();
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

    public function findPublicBySlug(string $slug): Brand
    {
        return $this->publicBrandQuery()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function localizedName(Brand $brand): string
    {
        if (app()->getLocale() === 'ur' && filled($brand->name_ur)) {
            return $brand->name_ur;
        }

        return $brand->name_en;
    }

    public function imageUrl(Brand $brand): ?string
    {
        return filled($brand->image_path)
            ? Storage::disk('public')->url($brand->image_path)
            : null;
    }

    public function localizedDescription(Brand $brand): ?string
    {
        if (app()->getLocale() === 'ur' && filled($brand->description_ur)) {
            return $brand->description_ur;
        }

        if (filled($brand->description_en)) {
            return $brand->description_en;
        }

        return filled($brand->description_ur) ? $brand->description_ur : null;
    }

    public function localizedReason(Brand $brand): ?string
    {
        if (app()->getLocale() === 'ur' && filled($brand->agricart_rating_reason_ur)) {
            return $brand->agricart_rating_reason_ur;
        }

        if (filled($brand->agricart_rating_reason_en)) {
            return $brand->agricart_rating_reason_en;
        }

        return filled($brand->agricart_rating_reason_ur) ? $brand->agricart_rating_reason_ur : null;
    }

    public function localizedRevision(Brand $brand): ?string
    {
        if (app()->getLocale() === 'ur' && filled($brand->agricart_rating_revision_ur)) {
            return $brand->agricart_rating_revision_ur;
        }

        if (filled($brand->agricart_rating_revision_en)) {
            return $brand->agricart_rating_revision_en;
        }

        return filled($brand->agricart_rating_revision_ur) ? $brand->agricart_rating_revision_ur : null;
    }

    /**
     * @return array{score: int, max: int, percent: int, stars: float}|null
     */
    public function ratingSummary(Brand $brand): ?array
    {
        $maxScore = BrandAgricartReviewCriteria::scoreMax();
        $rating = $brand->agricart_rating;

        if (! is_int($rating) || $rating < 0 || $rating > $maxScore) {
            return null;
        }

        $normalized = BrandAgricartReviewCriteria::normalizeDetailed(
            is_array($brand->agricart_rating_criteria) ? $brand->agricart_rating_criteria : null,
        );

        if (! BrandAgricartReviewCriteria::isComplete($normalized)) {
            return null;
        }

        return [
            'score' => $rating,
            'max' => $maxScore,
            'percent' => (int) round(($rating / $maxScore) * 100),
            'stars' => round($rating / 2, 1),
        ];
    }

    public function hasAgricartReview(Brand $brand): bool
    {
        return $this->ratingSummary($brand) !== null;
    }

    public function reviewNoticeText(): string
    {
        return \App\Models\StoreSetting::current()->brandReviewNoticeText();
    }

    /**
     * @return Builder<Brand>
     */
    private function publicBrandQuery(): Builder
    {
        return Brand::query()->where('status', 'active');
    }

    /**
     * Prefer brand identity matches; expand to descriptions / product titles for category-like terms.
     * Multi-word queries keep a full-phrase path so typing the complete brand name still works.
     *
     * @param  Builder<Brand>  $query
     */
    private function applyBrandSearch(Builder $query, string $term): void
    {
        $tokens = $this->significantSearchTokens($term);
        $escapedTerm = $this->escapeLike($term);
        $phraseNeedle = '%'.$escapedTerm.'%';

        $query->where(function (Builder $outer) use ($term, $tokens, $phraseNeedle): void {
            // 1) Full typed phrase against brand identity / short description.
            $outer->where(function (Builder $phrase) use ($phraseNeedle): void {
                $this->applyBrandIdentityMatch($phrase, $phraseNeedle)
                    ->orWhereRaw("description_en LIKE ? ESCAPE '!'", [$phraseNeedle])
                    ->orWhereRaw("description_ur LIKE ? ESCAPE '!'", [$phraseNeedle]);
            });

            if ($tokens === []) {
                return;
            }

            // 2) Every significant token must match brand identity (name/code/slug).
            $outer->orWhere(function (Builder $identity) use ($tokens): void {
                foreach ($tokens as $token) {
                    $needle = '%'.$this->escapeLike($token).'%';
                    $identity->where(function (Builder $inner) use ($needle): void {
                        $this->applyBrandIdentityMatch($inner, $needle);
                    });
                }
            });

            // 3) Category-style expansion: tokens may also hit brand description or product titles.
            $outer->orWhere(function (Builder $expanded) use ($tokens): void {
                foreach ($tokens as $token) {
                    $needle = '%'.$this->escapeLike($token).'%';
                    $expanded->where(function (Builder $inner) use ($needle): void {
                        $this->applyBrandExpandedToken($inner, $needle);
                    });
                }
            });
        });
    }

    /**
     * @param  Builder<Brand>  $query
     */
    private function applyBrandIdentityMatch(Builder $query, string $needle): Builder
    {
        return $query->where(function (Builder $brand) use ($needle): void {
            $brand->whereRaw("name_en LIKE ? ESCAPE '!'", [$needle])
                ->orWhereRaw("name_ur LIKE ? ESCAPE '!'", [$needle])
                ->orWhereRaw("code LIKE ? ESCAPE '!'", [$needle])
                ->orWhereRaw("slug LIKE ? ESCAPE '!'", [$needle]);
        });
    }

    /**
     * Broader match for terms like "bearing" — brand copy + product titles only (not SEO dumps).
     *
     * @param  Builder<Brand>  $query
     */
    private function applyBrandExpandedToken(Builder $query, string $needle): void
    {
        $query->where(function (Builder $brand) use ($needle): void {
            $this->applyBrandIdentityMatch($brand, $needle)
                ->orWhereRaw("description_en LIKE ? ESCAPE '!'", [$needle])
                ->orWhereRaw("description_ur LIKE ? ESCAPE '!'", [$needle])
                ->orWhereHas('products', function (Builder $products) use ($needle): void {
                    $products->whereIn('status', ['active', 'inactive'])
                        ->where(function (Builder $product) use ($needle): void {
                            $product->whereRaw("name_en LIKE ? ESCAPE '!'", [$needle])
                                ->orWhereRaw("name_ur LIKE ? ESCAPE '!'", [$needle])
                                ->orWhereRaw("slug LIKE ? ESCAPE '!'", [$needle])
                                ->orWhereRaw("code LIKE ? ESCAPE '!'", [$needle])
                                ->orWhereRaw("sku LIKE ? ESCAPE '!'", [$needle])
                                ->orWhereRaw("search_terms LIKE ? ESCAPE '!'", [$needle]);
                        });
                });
        });
    }

    /**
     * @param  Builder<Brand>  $query
     */
    private function orderByBrandSearchRelevance(Builder $query, string $term): void
    {
        $normalized = mb_strtolower(trim($term));
        $escaped = $this->escapeLike($normalized);
        $prefix = $escaped.'%';
        $contains = '%'.$escaped.'%';

        $query->orderByRaw(
            'CASE
                WHEN LOWER(COALESCE(name_en, \'\')) = ? OR LOWER(COALESCE(name_ur, \'\')) = ? THEN 0
                WHEN LOWER(COALESCE(code, \'\')) = ? OR LOWER(COALESCE(slug, \'\')) = ? THEN 1
                WHEN LOWER(COALESCE(name_en, \'\')) LIKE ? ESCAPE \'!\' OR LOWER(COALESCE(name_ur, \'\')) LIKE ? ESCAPE \'!\' THEN 2
                WHEN LOWER(COALESCE(name_en, \'\')) LIKE ? ESCAPE \'!\' OR LOWER(COALESCE(name_ur, \'\')) LIKE ? ESCAPE \'!\' THEN 3
                WHEN LOWER(COALESCE(code, \'\')) LIKE ? ESCAPE \'!\' OR LOWER(COALESCE(slug, \'\')) LIKE ? ESCAPE \'!\' THEN 4
                ELSE 5 END',
            [
                $normalized, $normalized,
                $normalized, $normalized,
                $prefix, $prefix,
                $contains, $contains,
                $contains, $contains,
            ],
        );
    }

    /**
     * Drop filler words so "SKF Brand" / "NBC Company Pakistan" still find the brand.
     *
     * @return list<string>
     */
    private function significantSearchTokens(string $term): array
    {
        $stopwords = [
            'a', 'an', 'and', 'the', 'of', 'for', 'to', 'in', 'on', 'at', 'by', 'with',
            'brand', 'brands', 'company', 'co', 'ltd', 'limited', 'pvt', 'private',
            'pakistan', 'pk', 'store', 'super', 'shop',
        ];

        $tokens = [];

        foreach ($this->searchTokens($term) as $token) {
            $normalized = mb_strtolower($token);

            if (mb_strlen($normalized) < 2) {
                continue;
            }

            if (in_array($normalized, $stopwords, true)) {
                continue;
            }

            $tokens[] = $token;
        }

        return array_values(array_unique($tokens));
    }

    /**
     * @return list<string>
     */
    private function searchTokens(string $term): array
    {
        $parts = preg_split('/[\s,\/|;+]+/u', trim($term), -1, PREG_SPLIT_NO_EMPTY);

        return is_array($parts) ? array_slice(array_values($parts), 0, 8) : [];
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
