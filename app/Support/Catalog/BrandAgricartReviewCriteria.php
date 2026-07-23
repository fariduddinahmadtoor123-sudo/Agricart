<?php

namespace App\Support\Catalog;

/**
 * Fixed Agricart brand review checklist (11 criteria).
 *
 * Stored shape per key:
 * { "rating": 1-10, "reason_en": "...", "reason_ur": "..." }
 *
 * Outcome bands (labels only — derived from rating):
 * - positive: 8–10
 * - neutral: 5–7
 * - needs_improvement: 1–4
 *
 * Legacy { pass: bool } rows are upgraded on normalize (true→9, false→3).
 */
final class BrandAgricartReviewCriteria
{
    public const KEYS = [
        'product_quality',
        'value_for_money',
        'market_reputation',
        'durability',
        'performance',
        'innovation_technology',
        'warranty_after_sales',
        'spare_parts_availability',
        'brand_reliability',
        'product_availability',
        'overall_market_acceptance',
    ];

    public const BAND_POSITIVE = 'positive';

    public const BAND_NEUTRAL = 'neutral';

    public const BAND_NEEDS_IMPROVEMENT = 'needs_improvement';

    public const MAX_RATING = 10;

    public const MIN_RATING = 1;

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return self::KEYS;
    }

    public static function count(): int
    {
        return count(self::KEYS);
    }

    /**
     * Overall score is an average of field ratings → out of 10.
     */
    public static function scoreMax(): int
    {
        return self::MAX_RATING;
    }

    /**
     * @return array{rating: int|null, reason_en: string, reason_ur: string}
     */
    public static function emptyDetail(): array
    {
        return [
            'rating' => null,
            'reason_en' => '',
            'reason_ur' => '',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $raw
     * @return array<string, array{rating: int|null, reason_en: string, reason_ur: string}>
     */
    public static function normalizeDetailed(?array $raw): array
    {
        $out = [];

        foreach (self::KEYS as $key) {
            $out[$key] = self::normalizeOne(is_array($raw) ? ($raw[$key] ?? null) : null);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>|null  $raw
     * @return array<string, int|null>
     */
    public static function normalize(?array $raw): array
    {
        $out = [];

        foreach (self::normalizeDetailed($raw) as $key => $detail) {
            $out[$key] = $detail['rating'];
        }

        return $out;
    }

    /**
     * @return array{rating: int|null, reason_en: string, reason_ur: string}
     */
    public static function normalizeOne(mixed $value): array
    {
        if ($value === null || $value === '') {
            return self::emptyDetail();
        }

        if (is_bool($value) || $value === 'pass' || $value === 'fail' || $value === 'true' || $value === 'false') {
            $rating = match (true) {
                $value === true, $value === 'pass', $value === 'true' => 9,
                $value === false, $value === 'fail', $value === 'false' => 3,
                default => null,
            };

            return [
                'rating' => $rating,
                'reason_en' => '',
                'reason_ur' => '',
            ];
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return [
                'rating' => self::clampRating((int) $value),
                'reason_en' => '',
                'reason_ur' => '',
            ];
        }

        if (! is_array($value)) {
            return self::emptyDetail();
        }

        $rating = null;

        if (array_key_exists('rating', $value) && $value['rating'] !== null && $value['rating'] !== '') {
            $rating = self::clampRating((int) $value['rating']);
        } elseif (array_key_exists('pass', $value) || array_key_exists('result', $value)) {
            $passRaw = $value['pass'] ?? $value['result'] ?? null;

            if ($passRaw === true || $passRaw === 1 || $passRaw === '1' || $passRaw === 'pass' || $passRaw === 'true') {
                $rating = 9;
            } elseif ($passRaw === false || $passRaw === 0 || $passRaw === '0' || $passRaw === 'fail' || $passRaw === 'false') {
                $rating = 3;
            }
        }

        return [
            'rating' => $rating,
            'reason_en' => trim((string) ($value['reason_en'] ?? '')),
            'reason_ur' => trim((string) ($value['reason_ur'] ?? '')),
        ];
    }

    public static function clampRating(int $rating): int
    {
        return max(self::MIN_RATING, min(self::MAX_RATING, $rating));
    }

    /**
     * @return self::BAND_*|null
     */
    public static function bandForRating(?int $rating): ?string
    {
        if ($rating === null) {
            return null;
        }

        $rating = self::clampRating($rating);

        if ($rating >= 8) {
            return self::BAND_POSITIVE;
        }

        if ($rating >= 5) {
            return self::BAND_NEUTRAL;
        }

        return self::BAND_NEEDS_IMPROVEMENT;
    }

    public static function bandLabel(string $band, string $locale = 'en'): string
    {
        return (string) __('agricart.brands.review_actions.'.$band, [], $locale);
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    public static function isComplete(array $criteria): bool
    {
        foreach (self::normalizeDetailed($criteria) as $detail) {
            if ($detail['rating'] === null) {
                return false;
            }

            if ($detail['reason_en'] === '' || $detail['reason_ur'] === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    public static function allOutcomesSet(array $criteria): bool
    {
        foreach (self::normalizeDetailed($criteria) as $detail) {
            if ($detail['rating'] === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    public static function hasAnyAnswer(array $criteria): bool
    {
        foreach (self::normalizeDetailed($criteria) as $detail) {
            if ($detail['rating'] !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Overall Agricart score = average of answered field ratings (1–10), rounded.
     *
     * @param  array<string, mixed>|null  $criteria
     */
    public static function score(?array $criteria): ?int
    {
        $detailed = self::normalizeDetailed($criteria);
        $sum = 0;
        $n = 0;

        foreach ($detailed as $detail) {
            if ($detail['rating'] === null) {
                continue;
            }

            $sum += $detail['rating'];
            $n++;
        }

        if ($n === 0) {
            return null;
        }

        return (int) round($sum / $n);
    }

    /**
     * @param  array<string, mixed>|null  $criteria
     * @return array<string, array{rating: int, reason_en: string, reason_ur: string}>|null
     */
    public static function forStorage(?array $criteria): ?array
    {
        $detailed = self::normalizeDetailed($criteria);

        if (! self::hasAnyAnswer($detailed)) {
            return null;
        }

        $stored = [];

        foreach ($detailed as $key => $detail) {
            if ($detail['rating'] === null) {
                continue;
            }

            $stored[$key] = [
                'rating' => $detail['rating'],
                'reason_en' => $detail['reason_en'],
                'reason_ur' => $detail['reason_ur'],
            ];
        }

        return $stored;
    }

    /**
     * @param  array<string, mixed>|null  $criteria
     * @return array{en: string|null, ur: string|null}
     */
    public static function composeReview(?array $criteria): array
    {
        $detailed = self::normalizeDetailed($criteria);

        if (! self::isComplete($detailed)) {
            return ['en' => null, 'ur' => null];
        }

        $score = self::score($detailed) ?? 0;
        $total = self::scoreMax();

        $enLines = [
            __('agricart.brands.review_compose.intro_en', ['score' => $score, 'total' => $total]),
            '',
        ];
        $urLines = [
            __('agricart.brands.review_compose.intro_ur', ['score' => $score, 'total' => $total]),
            '',
        ];

        foreach (self::KEYS as $key) {
            $detail = $detailed[$key];
            $band = self::bandForRating($detail['rating']) ?? self::BAND_NEUTRAL;

            $enLines[] = __('agricart.brands.review_compose.line_en', [
                'label' => __('agricart.brands.review_criteria.'.$key.'.label', [], 'en'),
                'result' => self::bandLabel($band, 'en').' ('.$detail['rating'].'/'.self::MAX_RATING.')',
                'reason' => $detail['reason_en'],
            ]);
            $urLines[] = __('agricart.brands.review_compose.line_ur', [
                'label' => __('agricart.brands.review_criteria.'.$key.'.label_ur', [], 'ur'),
                'result' => self::bandLabel($band, 'ur').' ('.$detail['rating'].'/'.self::MAX_RATING.')',
                'reason' => $detail['reason_ur'],
            ]);
        }

        return [
            'en' => implode("\n", $enLines),
            'ur' => implode("\n", $urLines),
        ];
    }

    public static function label(string $key, string $locale = 'en'): string
    {
        return (string) __('agricart.brands.review_criteria.'.$key.'.label', [], $locale);
    }

    public static function checkHint(string $key, string $locale = 'en'): string
    {
        return (string) __('agricart.brands.review_criteria.'.$key.'.check', [], $locale);
    }
}
