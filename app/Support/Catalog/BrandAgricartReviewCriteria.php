<?php

namespace App\Support\Catalog;

/**
 * Fixed Agricart brand review checklist (10 criteria → score out of 10).
 *
 * Stored shape per key:
 * { "pass": true|false, "reason_en": "...", "reason_ur": "..." }
 * Legacy boolean values are accepted and upgraded on normalize.
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
     * @return array{pass: bool|null, reason_en: string, reason_ur: string}
     */
    public static function emptyDetail(): array
    {
        return [
            'pass' => null,
            'reason_en' => '',
            'reason_ur' => '',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $raw
     * @return array<string, array{pass: bool|null, reason_en: string, reason_ur: string}>
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
     * Legacy bool-only map for score helpers that still expect true/false/null.
     *
     * @param  array<string, mixed>|null  $raw
     * @return array<string, bool|null>
     */
    public static function normalize(?array $raw): array
    {
        $out = [];

        foreach (self::normalizeDetailed($raw) as $key => $detail) {
            $out[$key] = $detail['pass'];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>|null  $raw
     * @return array{pass: bool|null, reason_en: string, reason_ur: string}
     */
    public static function normalizeOne(mixed $value): array
    {
        if ($value === null || $value === '') {
            return self::emptyDetail();
        }

        if (is_bool($value) || $value === 0 || $value === 1 || $value === '0' || $value === '1'
            || $value === 'pass' || $value === 'fail' || $value === 'true' || $value === 'false') {
            $pass = match (true) {
                $value === true, $value === 1, $value === '1', $value === 'pass', $value === 'true' => true,
                $value === false, $value === 0, $value === '0', $value === 'fail', $value === 'false' => false,
                default => null,
            };

            return [
                'pass' => $pass,
                'reason_en' => '',
                'reason_ur' => '',
            ];
        }

        if (! is_array($value)) {
            return self::emptyDetail();
        }

        $passRaw = $value['pass'] ?? $value['result'] ?? null;
        $pass = null;

        if ($passRaw === true || $passRaw === 1 || $passRaw === '1' || $passRaw === 'pass' || $passRaw === 'true') {
            $pass = true;
        } elseif ($passRaw === false || $passRaw === 0 || $passRaw === '0' || $passRaw === 'fail' || $passRaw === 'false') {
            $pass = false;
        }

        return [
            'pass' => $pass,
            'reason_en' => trim((string) ($value['reason_en'] ?? '')),
            'reason_ur' => trim((string) ($value['reason_ur'] ?? '')),
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    public static function isComplete(array $criteria): bool
    {
        foreach (self::normalizeDetailed($criteria) as $detail) {
            if ($detail['pass'] === null) {
                return false;
            }

            if ($detail['reason_en'] === '' || $detail['reason_ur'] === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * True when every checklist item has Pass or Fail (reasons may still be empty).
     *
     * @param  array<string, mixed>  $criteria
     */
    public static function allOutcomesSet(array $criteria): bool
    {
        foreach (self::normalizeDetailed($criteria) as $detail) {
            if ($detail['pass'] === null) {
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
            if ($detail['pass'] !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|null  $criteria
     */
    public static function score(?array $criteria): ?int
    {
        $detailed = self::normalizeDetailed($criteria);

        if (! self::hasAnyAnswer($detailed)) {
            return null;
        }

        $passes = 0;

        foreach ($detailed as $detail) {
            if ($detail['pass'] === true) {
                $passes++;
            }
        }

        return $passes;
    }

    /**
     * @param  array<string, mixed>|null  $criteria
     * @return array<string, array{pass: bool, reason_en: string, reason_ur: string}>|null
     */
    public static function forStorage(?array $criteria): ?array
    {
        $detailed = self::normalizeDetailed($criteria);

        if (! self::hasAnyAnswer($detailed)) {
            return null;
        }

        $stored = [];

        foreach ($detailed as $key => $detail) {
            if ($detail['pass'] === null) {
                continue;
            }

            $stored[$key] = [
                'pass' => $detail['pass'],
                'reason_en' => $detail['reason_en'],
                'reason_ur' => $detail['reason_ur'],
            ];
        }

        return $stored;
    }

    /**
     * Build bilingual Agricart review copy from the 10 checks + reasons.
     *
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
        $total = self::count();

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
            $resultEn = $detail['pass']
                ? __('agricart.brands.review_actions.pass')
                : __('agricart.brands.review_actions.fail');
            $resultUr = $detail['pass']
                ? __('agricart.brands.review_actions.pass', [], 'ur')
                : __('agricart.brands.review_actions.fail', [], 'ur');

            $enLines[] = __('agricart.brands.review_compose.line_en', [
                'label' => __('agricart.brands.review_criteria.'.$key.'.label', [], 'en'),
                'result' => $resultEn,
                'reason' => $detail['reason_en'],
            ]);
            $urLines[] = __('agricart.brands.review_compose.line_ur', [
                'label' => __('agricart.brands.review_criteria.'.$key.'.label_ur', [], 'ur'),
                'result' => $resultUr,
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
