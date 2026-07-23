<?php

namespace App\Services\Catalog;

use App\Models\BrandAgricartReviewMessage;
use App\Support\Catalog\BrandAgricartReviewCriteria;
use App\Support\Catalog\BrandAgricartReviewMessageDefaults;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BrandAgricartReviewMessageService
{
    /** @var list<string> */
    private const TEXT_KEYS = [
        'positive_en',
        'positive_ur',
        'neutral_en',
        'neutral_ur',
        'needs_improvement_en',
        'needs_improvement_ur',
    ];

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
     * @return list<array<string, string>>
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
            $fallback = BrandAgricartReviewMessageDefaults::for($key) ?? array_fill_keys(self::TEXT_KEYS, '');

            $row = [
                'criterion_key' => $key,
                'label' => BrandAgricartReviewCriteria::label($key, 'en'),
                'label_ur' => (string) __('agricart.brands.review_criteria.'.$key.'.label_ur', [], 'ur'),
            ];

            foreach (self::TEXT_KEYS as $field) {
                $row[$field] = filled($message?->{$field})
                    ? (string) $message->{$field}
                    : (string) ($fallback[$field] ?? '');
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array{en: string, ur: string}
     */
    public function suggestionFor(string $criterionKey, int $rating): array
    {
        $this->ensureSeeded();

        $band = BrandAgricartReviewCriteria::bandForRating($rating)
            ?? BrandAgricartReviewCriteria::BAND_NEUTRAL;

        $message = BrandAgricartReviewMessage::query()
            ->where('criterion_key', $criterionKey)
            ->first();

        $fallback = BrandAgricartReviewMessageDefaults::for($criterionKey) ?? array_fill_keys(self::TEXT_KEYS, '');

        $enKey = $band.'_en';
        $urKey = $band.'_ur';

        return [
            'en' => trim((string) (filled($message?->{$enKey}) ? $message->{$enKey} : ($fallback[$enKey] ?? ''))),
            'ur' => trim((string) (filled($message?->{$urKey}) ? $message->{$urKey} : ($fallback[$urKey] ?? ''))),
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, array{rating: int|null, reason_en: string, reason_ur: string}>
     */
    public function fillMissingReasons(array $criteria): array
    {
        $detailed = BrandAgricartReviewCriteria::normalizeDetailed($criteria);

        foreach (BrandAgricartReviewCriteria::keys() as $key) {
            $detail = $detailed[$key];

            if ($detail['rating'] === null) {
                continue;
            }

            if ($detail['reason_en'] !== '' && $detail['reason_ur'] !== '') {
                continue;
            }

            $suggestion = $this->suggestionFor($key, (int) $detail['rating']);

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
     * @param  array{en: string, ur: string}  $text
     */
    public function saveBandDefault(string $criterionKey, string $band, array $text): void
    {
        if (! in_array($criterionKey, BrandAgricartReviewCriteria::keys(), true)) {
            throw ValidationException::withMessages([
                'criterion_key' => [__('agricart.brands.review_messages.invalid_criterion')],
            ]);
        }

        if (! in_array($band, [
            BrandAgricartReviewCriteria::BAND_POSITIVE,
            BrandAgricartReviewCriteria::BAND_NEUTRAL,
            BrandAgricartReviewCriteria::BAND_NEEDS_IMPROVEMENT,
        ], true)) {
            throw ValidationException::withMessages([
                'band' => [__('agricart.brands.review_messages.invalid_band')],
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
        $fallback = BrandAgricartReviewMessageDefaults::for($criterionKey) ?? array_fill_keys(self::TEXT_KEYS, '');

        if (! $row->exists) {
            foreach (self::TEXT_KEYS as $field) {
                $row->{$field} = $fallback[$field] ?? '';
            }
        }

        $row->{$band.'_en'} = $en;
        $row->{$band.'_ur'} = $ur;
        $row->save();
    }

    /**
     * @param  array<string, array<string, string>>  $rows
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

                $data = [];

                foreach (self::TEXT_KEYS as $field) {
                    $value = trim((string) ($payload[$field] ?? ''));

                    if ($value === '') {
                        throw ValidationException::withMessages([
                            $key => [__('agricart.brands.review_messages.all_fields_required', [
                                'label' => BrandAgricartReviewCriteria::label($key),
                            ])],
                        ]);
                    }

                    if (mb_strlen($value) > 2000) {
                        throw ValidationException::withMessages([
                            $key => [__('agricart.brands.review_messages.too_long')],
                        ]);
                    }

                    $data[$field] = $value;
                }

                BrandAgricartReviewMessage::query()->updateOrCreate(
                    ['criterion_key' => $key],
                    $data,
                );
            }
        });
    }
}
