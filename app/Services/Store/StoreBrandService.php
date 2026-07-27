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
            $this->applyBrandSearch($query, trim((string) $search));
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
     * Match brand names/descriptions and storefront products sold under the brand.
     *
     * @param  Builder<Brand>  $query
     */
    private function applyBrandSearch(Builder $query, string $term): void
    {
        $tokens = $this->searchTokens($term);
        $matchedAnyToken = false;

        foreach ($tokens as $token) {
            if (mb_strlen($token) < 2) {
                continue;
            }

            $matchedAnyToken = true;
            $needle = '%'.$this->escapeLike($token).'%';

            $query->where(function (Builder $inner) use ($needle): void {
                $this->applyBrandToken($inner, $needle);
            });
        }

        if (! $matchedAnyToken) {
            $needle = '%'.$this->escapeLike($term).'%';

            $query->where(function (Builder $inner) use ($needle): void {
                $this->applyBrandToken($inner, $needle);
            });
        }
    }

    /**
     * @param  Builder<Brand>  $query
     */
    private function applyBrandToken(Builder $query, string $needle): void
    {
        $query->where(function (Builder $brand) use ($needle): void {
            $brand->whereRaw("name_en LIKE ? ESCAPE '!'", [$needle])
                ->orWhereRaw("name_ur LIKE ? ESCAPE '!'", [$needle])
                ->orWhereRaw("code LIKE ? ESCAPE '!'", [$needle])
                ->orWhereRaw("slug LIKE ? ESCAPE '!'", [$needle])
                ->orWhereRaw("description_en LIKE ? ESCAPE '!'", [$needle])
                ->orWhereRaw("description_ur LIKE ? ESCAPE '!'", [$needle])
                ->orWhereRaw("agricart_rating_reason_en LIKE ? ESCAPE '!'", [$needle])
                ->orWhereRaw("agricart_rating_reason_ur LIKE ? ESCAPE '!'", [$needle])
                ->orWhereHas('products', function (Builder $products) use ($needle): void {
                    $products->whereIn('status', ['active', 'inactive'])
                        ->where(function (Builder $product) use ($needle): void {
                            $product->whereRaw("name_en LIKE ? ESCAPE '!'", [$needle])
                                ->orWhereRaw("name_ur LIKE ? ESCAPE '!'", [$needle])
                                ->orWhereRaw("slug LIKE ? ESCAPE '!'", [$needle])
                                ->orWhereRaw("code LIKE ? ESCAPE '!'", [$needle])
                                ->orWhereRaw("sku LIKE ? ESCAPE '!'", [$needle])
                                ->orWhereRaw("short_description_en LIKE ? ESCAPE '!'", [$needle])
                                ->orWhereRaw("short_description_ur LIKE ? ESCAPE '!'", [$needle])
                                ->orWhereRaw("full_description_en LIKE ? ESCAPE '!'", [$needle])
                                ->orWhereRaw("full_description_ur LIKE ? ESCAPE '!'", [$needle])
                                ->orWhereRaw("search_terms LIKE ? ESCAPE '!'", [$needle])
                                ->orWhereRaw("seo_keywords_en LIKE ? ESCAPE '!'", [$needle])
                                ->orWhereRaw("seo_keywords_ur LIKE ? ESCAPE '!'", [$needle]);
                        });
                });
        });
    }

    /**
     * @return list<string>
     */
    private function searchTokens(string $term): array
    {
        $parts = preg_split('/\s+/u', trim($term), -1, PREG_SPLIT_NO_EMPTY);

        return is_array($parts) ? array_slice(array_values($parts), 0, 8) : [];
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
