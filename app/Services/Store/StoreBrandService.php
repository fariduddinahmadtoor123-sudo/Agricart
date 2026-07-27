<?php

namespace App\Services\Store;

use App\Models\Brand;
use App\Support\Catalog\BrandAgricartReviewCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class StoreBrandService
{
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
     * @return Collection<int, Brand>
     */
    public function listedBrands(): Collection
    {
        return $this->publicBrandQuery()
            ->orderBy('name_en')
            ->get();
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
}
