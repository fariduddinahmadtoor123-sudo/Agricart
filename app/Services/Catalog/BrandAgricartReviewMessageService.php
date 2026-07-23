<?php

namespace App\Services\Catalog;

use App\Models\BrandAgricartReviewMessage;
use App\Support\Catalog\BrandAgricartReviewCriteria;
use App\Support\Catalog\BrandAgricartReviewMessageDefaults;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BrandAgricartReviewMessageService
{
    /**
     * Ensure every checklist key has a DB row (seeded from factory defaults when missing).
     */
    public function ensureSeeded(): void
    {
        $existing = BrandAgricartReviewMessage::query()
            ->pluck('criterion_key')
            ->all();

        $defaults = BrandAgricartReviewMessageDefaults::all();

        foreach (BrandAgricartReviewCriteria::keys() as $key) {
            if (in_array($key, $existing, true)) {
                continue;
            }

            $row = $defaults[$key] ?? null;

            if ($row === null) {
                continue;
            }

            BrandAgricartReviewMessage::query()->create([
                'criterion_key' => $key,
                ...$row,
            ]);
        }
    }

    /**
     * @return list<array{
     *     criterion_key: string,
     *     label: string,
     *     label_ur: string,
     *     high_en: string,
     *     high_ur: string,
     *     low_en: string,
     *     low_ur: string
     * }>
     */
    public function listManaged(): array
    {
        $this->ensureSeeded();

        $byKey = BrandAgricartReviewMessage::query()
            ->get()
            ->keyBy('criterion_key');

        $rows = [];

        foreach (BrandAgricartReviewCriteria::keys() as $key) {
            $message = $byKey->get($key);
            $fallback = BrandAgricartReviewMessageDefaults::for($key) ?? [
                'high_en' => '',
                'high_ur' => '',
                'low_en' => '',
                'low_ur' => '',
            ];

            $rows[] = [
                'criterion_key' => $key,
                'label' => BrandAgricartReviewCriteria::label($key, 'en'),
                'label_ur' => (string) __('agricart.brands.review_criteria.'.$key.'.label_ur', [], 'ur'),
                'high_en' => filled($message?->high_en) ? (string) $message->high_en : $fallback['high_en'],
                'high_ur' => filled($message?->high_ur) ? (string) $message->high_ur : $fallback['high_ur'],
                'low_en' => filled($message?->low_en) ? (string) $message->low_en : $fallback['low_en'],
                'low_ur' => filled($message?->low_ur) ? (string) $message->low_ur : $fallback['low_ur'],
            ];
        }

        return $rows;
    }

    /**
     * @return array{en: string, ur: string}
     */
    public function suggestionFor(string $criterionKey, bool $pass): array
    {
        $this->ensureSeeded();

        $message = BrandAgricartReviewMessage::query()
            ->where('criterion_key', $criterionKey)
            ->first();

        $fallback = BrandAgricartReviewMessageDefaults::for($criterionKey) ?? [
            'high_en' => '',
            'high_ur' => '',
            'low_en' => '',
            'low_ur' => '',
        ];

        if ($pass) {
            return [
                'en' => trim((string) (filled($message?->high_en) ? $message->high_en : $fallback['high_en'])),
                'ur' => trim((string) (filled($message?->high_ur) ? $message->high_ur : $fallback['high_ur'])),
            ];
        }

        return [
            'en' => trim((string) (filled($message?->low_en) ? $message->low_en : $fallback['low_en'])),
            'ur' => trim((string) (filled($message?->low_ur) ? $message->low_ur : $fallback['low_ur'])),
        ];
    }

    /**
     * Fill empty reason_en/ur for answered Pass/Fail rows from manageable defaults.
     *
     * @param  array<string, mixed>  $criteria
     * @return array<string, array{pass: bool|null, reason_en: string, reason_ur: string}>
     */
    public function fillMissingReasons(array $criteria): array
    {
        $detailed = BrandAgricartReviewCriteria::normalizeDetailed($criteria);

        foreach (BrandAgricartReviewCriteria::keys() as $key) {
            $detail = $detailed[$key];

            if ($detail['pass'] === null) {
                continue;
            }

            if ($detail['reason_en'] !== '' && $detail['reason_ur'] !== '') {
                continue;
            }

            $suggestion = $this->suggestionFor($key, (bool) $detail['pass']);

            if ($detail['reason_en'] === '' && $suggestion['en'] !== '') {
                $detailed[$key]['reason_en'] = $suggestion['en'];
            }

            if ($detail['reason_ur'] === '' && $suggestion['ur'] !== '') {
                $detailed[$key]['reason_ur'] = $suggestion['ur'];
            }
        }

        return $detailed;
    }

    /**
     * Persist Pass (high) or Fail (low) default messages for one criterion.
     *
     * @param  array{en: string, ur: string}  $text
     */
    public function saveOutcomeDefault(string $criterionKey, bool $pass, array $text): void
    {
        if (! in_array($criterionKey, BrandAgricartReviewCriteria::keys(), true)) {
            throw ValidationException::withMessages([
                'criterion_key' => [__('agricart.brands.review_messages.invalid_criterion')],
            ]);
        }

        $en = trim($text['en'] ?? '');
        $ur = trim($text['ur'] ?? '');

        if ($en === '' || $ur === '') {
            throw ValidationException::withMessages([
                'reason' => [__('agricart.brands.review_messages.both_languages_required')],
            ]);
        }

        if (mb_strlen($en) > 2000 || mb_strlen($ur) > 2000) {
            throw ValidationException::withMessages([
                'reason' => [__('agricart.brands.review_messages.too_long')],
            ]);
        }

        $this->ensureSeeded();

        $row = BrandAgricartReviewMessage::query()->firstOrNew(['criterion_key' => $criterionKey]);

        if ($pass) {
            $row->high_en = $en;
            $row->high_ur = $ur;
        } else {
            $row->low_en = $en;
            $row->low_ur = $ur;
        }

        if (! $row->exists) {
            $fallback = BrandAgricartReviewMessageDefaults::for($criterionKey) ?? [
                'high_en' => '',
                'high_ur' => '',
                'low_en' => '',
                'low_ur' => '',
            ];
            $row->high_en ??= $fallback['high_en'];
            $row->high_ur ??= $fallback['high_ur'];
            $row->low_en ??= $fallback['low_en'];
            $row->low_ur ??= $fallback['low_ur'];
        }

        $row->save();
    }

    /**
     * @param  array<string, array{high_en: string, high_ur: string, low_en: string, low_ur: string}>  $rows
     */
    public function saveAll(array $rows): void
    {
        $this->ensureSeeded();

        DB::transaction(function () use ($rows): void {
            foreach (BrandAgricartReviewCriteria::keys() as $key) {
                $payload = $rows[$key] ?? null;

                if (! is_array($payload)) {
                    continue;
                }

                $highEn = trim((string) ($payload['high_en'] ?? ''));
                $highUr = trim((string) ($payload['high_ur'] ?? ''));
                $lowEn = trim((string) ($payload['low_en'] ?? ''));
                $lowUr = trim((string) ($payload['low_ur'] ?? ''));

                if ($highEn === '' || $highUr === '' || $lowEn === '' || $lowUr === '') {
                    throw ValidationException::withMessages([
                        $key => [__('agricart.brands.review_messages.all_fields_required', [
                            'label' => BrandAgricartReviewCriteria::label($key),
                        ])],
                    ]);
                }

                foreach ([$highEn, $highUr, $lowEn, $lowUr] as $value) {
                    if (mb_strlen($value) > 2000) {
                        throw ValidationException::withMessages([
                            $key => [__('agricart.brands.review_messages.too_long')],
                        ]);
                    }
                }

                BrandAgricartReviewMessage::query()->updateOrCreate(
                    ['criterion_key' => $key],
                    [
                        'high_en' => $highEn,
                        'high_ur' => $highUr,
                        'low_en' => $lowEn,
                        'low_ur' => $lowUr,
                    ],
                );
            }
        });
    }
}
