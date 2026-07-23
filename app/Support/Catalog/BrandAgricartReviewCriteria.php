<?php

namespace App\Support\Catalog;

/**
 * Fixed Agricart brand review checklist (10 criteria → score out of 10).
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
        'overall_market_acceptance',
    ];

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
     * Normalize stored/posted map to known keys only (true|false|null).
     *
     * @param  array<string, mixed>|null  $raw
     * @return array<string, bool|null>
     */
    public static function normalize(?array $raw): array
    {
        $out = [];

        foreach (self::KEYS as $key) {
            if (! is_array($raw) || ! array_key_exists($key, $raw)) {
                $out[$key] = null;

                continue;
            }

            $value = $raw[$key];

            if ($value === null || $value === '') {
                $out[$key] = null;
            } elseif ($value === true || $value === 1 || $value === '1' || $value === 'pass' || $value === 'true') {
                $out[$key] = true;
            } elseif ($value === false || $value === 0 || $value === '0' || $value === 'fail' || $value === 'false') {
                $out[$key] = false;
            } else {
                $out[$key] = null;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, bool|null>  $criteria
     */
    public static function isComplete(array $criteria): bool
    {
        $normalized = self::normalize($criteria);

        foreach ($normalized as $value) {
            if ($value === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, bool|null>  $criteria
     */
    public static function hasAnyAnswer(array $criteria): bool
    {
        foreach (self::normalize($criteria) as $value) {
            if ($value !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pass count = Agricart score out of 10 (null when nothing answered).
     *
     * @param  array<string, bool|null>  $criteria
     */
    public static function score(?array $criteria): ?int
    {
        $normalized = self::normalize($criteria);

        if (! self::hasAnyAnswer($normalized)) {
            return null;
        }

        $passes = 0;

        foreach ($normalized as $value) {
            if ($value === true) {
                $passes++;
            }
        }

        return $passes;
    }

    /**
     * Persistable map (omit nulls so empty review stores null/empty).
     *
     * @param  array<string, bool|null>  $criteria
     * @return array<string, bool>|null
     */
    public static function forStorage(?array $criteria): ?array
    {
        $normalized = self::normalize($criteria);

        if (! self::hasAnyAnswer($normalized)) {
            return null;
        }

        $stored = [];

        foreach ($normalized as $key => $value) {
            if ($value !== null) {
                $stored[$key] = $value;
            }
        }

        return $stored;
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
