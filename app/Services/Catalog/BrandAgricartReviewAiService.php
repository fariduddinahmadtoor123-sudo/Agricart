<?php

namespace App\Services\Catalog;

use App\Enums\AiLogType;
use App\Models\AiConfiguration;
use App\Services\Ai\AiConfigurationService;
use App\Services\Ai\OpenRouterClient;
use App\Support\Catalog\BrandAgricartReviewCriteria;

class BrandAgricartReviewAiService
{
    public function __construct(
        private readonly AiConfigurationService $ai,
        private readonly OpenRouterClient $openRouter,
    ) {}

    /**
     * Generate per-criterion reasons + polished bilingual Agricart review from 1–10 ratings.
     *
     * @param  array<string, mixed>  $criteria
     * @return array{success: bool, message: string, data?: array{criteria: array<string, array{reason_en: string, reason_ur: string}>, review_en: string, review_ur: string}}
     */
    public function generate(string $brandNameEn, ?string $brandNameUr, array $criteria): array
    {
        $configuration = $this->ai->active();

        if ($configuration === null) {
            return [
                'success' => false,
                'message' => __('agricart.brands.ai.no_configuration'),
            ];
        }

        if ($configuration->isPaused()) {
            return [
                'success' => false,
                'message' => __('agricart.ai.messages.paused'),
            ];
        }

        $brandNameEn = trim($brandNameEn);

        if ($brandNameEn === '') {
            return [
                'success' => false,
                'message' => __('agricart.brands.ai.review_name_required'),
            ];
        }

        $detailed = BrandAgricartReviewCriteria::normalizeDetailed($criteria);

        if (! BrandAgricartReviewCriteria::allOutcomesSet($detailed)) {
            return [
                'success' => false,
                'message' => __('agricart.brands.ai.review_outcomes_required'),
            ];
        }

        $prompt = $this->buildPrompt($brandNameEn, trim((string) $brandNameUr), $detailed);
        $result = $this->callAi($configuration, $prompt);

        if (! $result['success']) {
            return $result;
        }

        $parsed = $this->parseResponse((string) $result['response']);

        if ($parsed === null) {
            return [
                'success' => false,
                'message' => __('agricart.brands.ai.review_invalid_json'),
            ];
        }

        return [
            'success' => true,
            'message' => __('agricart.brands.ai.review_generated'),
            'data' => $parsed,
        ];
    }

    /**
     * @param  array<string, array{rating: int|null, reason_en: string, reason_ur: string}>  $detailed
     */
    private function buildPrompt(string $brandNameEn, string $brandNameUr, array $detailed): string
    {
        $score = BrandAgricartReviewCriteria::score($detailed) ?? 0;
        $total = BrandAgricartReviewCriteria::scoreMax();
        $lines = [];

        foreach (BrandAgricartReviewCriteria::KEYS as $key) {
            $detail = $detailed[$key];
            $rating = (int) $detail['rating'];
            $band = BrandAgricartReviewCriteria::bandForRating($rating) ?? BrandAgricartReviewCriteria::BAND_NEUTRAL;
            $bandLabel = strtoupper(str_replace('_', ' ', $band));
            $label = BrandAgricartReviewCriteria::label($key, 'en');
            $hint = BrandAgricartReviewCriteria::checkHint($key, 'en');
            $line = "- {$key}: RATING {$rating}/10 ({$bandLabel}) — {$label}. Check: {$hint}";

            if ($detail['reason_en'] !== '' || $detail['reason_ur'] !== '') {
                $line .= ' Existing notes (improve or keep spirit):'
                    .($detail['reason_en'] !== '' ? ' EN="'.$detail['reason_en'].'"' : '')
                    .($detail['reason_ur'] !== '' ? ' UR="'.$detail['reason_ur'].'"' : '');
            }

            $lines[] = $line;
        }

        $checklist = implode("\n", $lines);
        $urduName = $brandNameUr !== '' ? $brandNameUr : $brandNameEn;

        return <<<PROMPT
You write Agricart brand reviews for a Pakistani agriculture retail business (farm tools, machinery, inputs, spare parts).

Brand English name: {$brandNameEn}
Brand Urdu name: {$urduName}
Agricart overall score (average of field ratings): {$score} / {$total}

Fixed 1–10 ratings and outcome bands (do NOT change ratings — only explain them):
- 8–10 = Positive
- 5–7 = Neutral
- 1–4 = Needs Improvement

{$checklist}

Tasks:
1. For EVERY checklist key, write a short, specific reason in English and Urdu matching that field's rating band for this brand in Pakistan's agri market. 1–3 sentences each. Be fair and professional: Positive = clear strength; Neutral = acceptable with room to grow; Needs Improvement = real weakness without harsh language. Do not invent fake certifications, awards, or numbers.
2. Write one polished overall Agricart review in English and one in Urdu (paragraph style, not a bullet dump). Reflect the real rating pattern: praise strengths, note neutral areas calmly, clearly mention needs-improvement areas, end with fair buying guidance for farmers/retailers. Urdu must be natural Pakistani Urdu (not transliteration of English).

Return ONLY valid JSON (no markdown) with this exact shape:
{
  "criteria": {
    "product_quality": {"reason_en": "...", "reason_ur": "..."},
    "value_for_money": {"reason_en": "...", "reason_ur": "..."},
    "market_reputation": {"reason_en": "...", "reason_ur": "..."},
    "durability": {"reason_en": "...", "reason_ur": "..."},
    "performance": {"reason_en": "...", "reason_ur": "..."},
    "innovation_technology": {"reason_en": "...", "reason_ur": "..."},
    "warranty_after_sales": {"reason_en": "...", "reason_ur": "..."},
    "spare_parts_availability": {"reason_en": "...", "reason_ur": "..."},
    "brand_reliability": {"reason_en": "...", "reason_ur": "..."},
    "product_availability": {"reason_en": "...", "reason_ur": "..."},
    "overall_market_acceptance": {"reason_en": "...", "reason_ur": "..."}
  },
  "review_en": "...",
  "review_ur": "..."
}
PROMPT;
    }

    /**
     * @return array{success: bool, message: string, response?: string|null}
     */
    private function callAi(AiConfiguration $configuration, string $prompt): array
    {
        $start = microtime(true);
        $result = $this->openRouter->sendChat(
            $configuration->api_key,
            $configuration->model_id,
            [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            8192,
            true,
        );

        $this->logPrompt($configuration, $prompt, $result, (int) ((microtime(true) - $start) * 1000));

        return $result;
    }

    /**
     * @param  array{success: bool, message: string, response: string|null, http_status?: int|null}  $result
     */
    private function logPrompt(AiConfiguration $configuration, string $prompt, array $result, int $durationMs): void
    {
        app(\App\Services\Ai\AiLogService::class)->fromConfiguration(
            $configuration,
            AiLogType::BrandAgricartReview,
            'brand_agricart_review',
            $result['success'],
            $prompt,
            $result['response'],
            $result['success'] ? null : $result['message'],
            $result['http_status'] ?? null,
            $durationMs,
        );
    }

    /**
     * @return array{criteria: array<string, array{reason_en: string, reason_ur: string}>, review_en: string, review_ur: string}|null
     */
    public function parseResponse(string $response): ?array
    {
        $json = trim($response);

        if (str_starts_with($json, '```')) {
            $json = preg_replace('/^```(?:json)?\s*/i', '', $json) ?? $json;
            $json = preg_replace('/\s*```$/', '', $json) ?? $json;
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded) && preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            return null;
        }

        return $this->normalizePayload($decoded);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{criteria: array<string, array{reason_en: string, reason_ur: string}>, review_en: string, review_ur: string}|null
     */
    private function normalizePayload(array $payload): ?array
    {
        $reviewEn = trim((string) ($payload['review_en'] ?? ''));
        $reviewUr = trim((string) ($payload['review_ur'] ?? ''));

        if ($reviewEn === '' || $reviewUr === '') {
            return null;
        }

        if (mb_strlen($reviewEn) > 20_000 || mb_strlen($reviewUr) > 20_000) {
            return null;
        }

        $rawCriteria = $payload['criteria'] ?? null;

        if (! is_array($rawCriteria)) {
            return null;
        }

        $criteria = [];

        foreach (BrandAgricartReviewCriteria::KEYS as $key) {
            $row = $rawCriteria[$key] ?? null;

            if (! is_array($row)) {
                return null;
            }

            $reasonEn = trim((string) ($row['reason_en'] ?? ''));
            $reasonUr = trim((string) ($row['reason_ur'] ?? ''));

            if ($reasonEn === '' || $reasonUr === '') {
                return null;
            }

            if (mb_strlen($reasonEn) > 2000 || mb_strlen($reasonUr) > 2000) {
                return null;
            }

            $criteria[$key] = [
                'reason_en' => $reasonEn,
                'reason_ur' => $reasonUr,
            ];
        }

        return [
            'criteria' => $criteria,
            'review_en' => $reviewEn,
            'review_ur' => $reviewUr,
        ];
    }
}
