<?php

namespace App\Services\Catalog;

use App\Enums\AiLogType;
use App\Enums\CategoryAiStatus;
use App\Models\AiConfiguration;
use App\Services\Ai\AiConfigurationService;
use App\Services\Ai\OpenRouterClient;

class AttributeEnrichmentService
{
    public function __construct(
        private readonly AiConfigurationService $ai,
        private readonly OpenRouterClient $openRouter,
    ) {}

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function enrich(string $nameEn, string $nameUr): array
    {
        $configuration = $this->ai->active();

        if ($configuration === null) {
            return [
                'success' => false,
                'message' => __('agricart.attributes.ai.no_configuration'),
            ];
        }

        if ($configuration->isPaused()) {
            return [
                'success' => false,
                'message' => __('agricart.ai.messages.paused'),
            ];
        }

        if (! filled(trim($nameEn)) && ! filled(trim($nameUr))) {
            return [
                'success' => false,
                'message' => __('agricart.attributes.ai.name_required'),
            ];
        }

        $trimmedEn = trim($nameEn);
        $trimmedUr = trim($nameUr);
        $hasEn = filled($trimmedEn);
        $hasUr = filled($trimmedUr);

        $direction = match (true) {
            $hasEn && ! $hasUr => __('agricart.attributes.ai.direction_en_to_ur', ['name' => $trimmedEn]),
            $hasUr && ! $hasEn => __('agricart.attributes.ai.direction_ur_to_en', ['name' => $trimmedUr]),
            default => __('agricart.attributes.ai.direction_both'),
        };

        $prompt = __('agricart.attributes.ai.prompt', [
            'name_en' => $hasEn ? $trimmedEn : '—',
            'name_ur' => $hasUr ? $trimmedUr : '—',
        ])
            . "\n\n" . $direction
            . "\n\nReturn ONLY valid JSON (no markdown) with keys: name_en, name_ur. "
            . 'Use standard product attribute names for agriculture and hardware stores in Pakistan.';

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
            512,
        );

        $this->logPrompt($configuration, $prompt, $result, (int) ((microtime(true) - $start) * 1000));

        if (! $result['success']) {
            return $result;
        }

        $parsed = $this->parseResponse((string) $result['response']);

        if ($parsed === null) {
            return [
                'success' => false,
                'message' => __('agricart.attributes.ai.invalid_json'),
            ];
        }

        $parsed = $this->mergeKnownValues($parsed, $trimmedEn, $trimmedUr);

        if ($hasEn && ! $hasUr) {
            $urdu = trim((string) ($parsed['name_ur'] ?? ''));

            if ($urdu === '' || strcasecmp($urdu, $trimmedEn) === 0) {
                return [
                    'success' => false,
                    'message' => __('agricart.attributes.ai.missing_ur'),
                ];
            }
        }

        if ($hasUr && ! $hasEn && ! filled($parsed['name_en'] ?? null)) {
            return [
                'success' => false,
                'message' => __('agricart.attributes.ai.missing_en'),
            ];
        }

        return [
            'success' => true,
            'message' => __('agricart.attributes.ai.enriched'),
            'data' => $parsed,
        ];
    }

    /**
     * @param  array{success: bool, message: string, response: string|null, http_status?: int|null}  $result
     */
    private function logPrompt(AiConfiguration $configuration, string $prompt, array $result, int $durationMs): void
    {
        app(\App\Services\Ai\AiLogService::class)->fromConfiguration(
            $configuration,
            AiLogType::AttributeEnrichment,
            'attribute_form',
            $result['success'],
            $prompt,
            $result['response'],
            $result['success'] ? null : $result['message'],
            $result['http_status'] ?? null,
            $durationMs,
        );
    }

    /**
     * @return array<string, mixed>|null
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
     * @return array<string, mixed>|null
     */
    private function normalizePayload(array $payload): ?array
    {
        $normalized = [];

        foreach (['name_en', 'name_ur'] as $key) {
            if (isset($payload[$key]) && is_string($payload[$key]) && filled(trim($payload[$key]))) {
                $value = trim($payload[$key]);

                if (mb_strlen($value) > 128) {
                    return null;
                }

                $normalized[$key] = $value;
            }
        }

        if ($normalized === []) {
            return null;
        }

        $normalized['ai_status'] = CategoryAiStatus::Enriched->value;

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    private function mergeKnownValues(array $parsed, string $nameEn, string $nameUr): array
    {
        if (filled($nameEn)) {
            $parsed['name_en'] = $nameEn;
        }

        if (filled($nameUr)) {
            $parsed['name_ur'] = $nameUr;
        }

        return $parsed;
    }
}
