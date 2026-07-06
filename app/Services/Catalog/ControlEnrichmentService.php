<?php

namespace App\Services\Catalog;

use App\Enums\AiLogType;
use App\Enums\CategoryAiStatus;
use App\Models\AiConfiguration;
use App\Services\Ai\AiConfigurationService;
use App\Services\Ai\OpenRouterClient;

class ControlEnrichmentService
{
    public function __construct(
        private readonly AiConfigurationService $ai,
        private readonly OpenRouterClient $openRouter,
    ) {}

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function enrich(
        string $nameEn,
        string $nameUr,
        string $valueEn,
        string $valueUr,
    ): array {
        $configuration = $this->ai->active();

        if ($configuration === null) {
            return [
                'success' => false,
                'message' => __('agricart.controls.ai.no_configuration'),
            ];
        }

        if ($configuration->isPaused()) {
            return [
                'success' => false,
                'message' => __('agricart.ai.messages.paused'),
            ];
        }

        $nameEn = trim($nameEn);
        $nameUr = trim($nameUr);
        $valueEn = trim($valueEn);
        $valueUr = trim($valueUr);

        $englishPair = filled($nameEn) && filled($valueEn);
        $urduPair = filled($nameUr) && filled($valueUr);

        if (! $englishPair && ! $urduPair) {
            return [
                'success' => false,
                'message' => __('agricart.controls.ai.pair_required'),
            ];
        }

        $direction = match (true) {
            $englishPair && ! $urduPair => __('agricart.controls.ai.direction_en_to_ur', [
                'name' => $nameEn,
                'value' => $valueEn,
            ]),
            $urduPair && ! $englishPair => __('agricart.controls.ai.direction_ur_to_en', [
                'name' => $nameUr,
                'value' => $valueUr,
            ]),
            default => __('agricart.controls.ai.direction_both'),
        };

        $prompt = __('agricart.controls.ai.prompt', [
            'name_en' => filled($nameEn) ? $nameEn : '—',
            'name_ur' => filled($nameUr) ? $nameUr : '—',
            'value_en' => filled($valueEn) ? $valueEn : '—',
            'value_ur' => filled($valueUr) ? $valueUr : '—',
        ])
            . "\n\n" . $direction
            . "\n\nReturn ONLY valid JSON (no markdown) with keys: name_en, name_ur, value_en, value_ur. "
            . 'A control is a product specification label with its value (e.g. Voltage / 220V). Use agriculture and hardware store terminology in Pakistan.';

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
            768,
        );

        $this->logPrompt($configuration, $prompt, $result, (int) ((microtime(true) - $start) * 1000));

        if (! $result['success']) {
            return $result;
        }

        $parsed = $this->parseResponse((string) $result['response']);

        if ($parsed === null) {
            return [
                'success' => false,
                'message' => __('agricart.controls.ai.invalid_json'),
            ];
        }

        $parsed = $this->mergeKnownValues($parsed, $nameEn, $nameUr, $valueEn, $valueUr);

        if ($englishPair && ! $urduPair) {
            if (! filled($parsed['name_ur'] ?? null) || strcasecmp((string) $parsed['name_ur'], $nameEn) === 0) {
                return [
                    'success' => false,
                    'message' => __('agricart.controls.ai.missing_ur'),
                ];
            }

            if (! filled($parsed['value_ur'] ?? null) || strcasecmp((string) $parsed['value_ur'], $valueEn) === 0) {
                return [
                    'success' => false,
                    'message' => __('agricart.controls.ai.missing_ur'),
                ];
            }
        }

        if ($urduPair && ! $englishPair) {
            if (! filled($parsed['name_en'] ?? null)) {
                return [
                    'success' => false,
                    'message' => __('agricart.controls.ai.missing_en'),
                ];
            }

            if (! filled($parsed['value_en'] ?? null)) {
                return [
                    'success' => false,
                    'message' => __('agricart.controls.ai.missing_en'),
                ];
            }
        }

        return [
            'success' => true,
            'message' => __('agricart.controls.ai.enriched'),
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
            AiLogType::ControlEnrichment,
            'control_form',
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

        if (! is_array($decoded)) {
            return null;
        }

        return $this->normalizePayload($decoded);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizePayload(array $payload): array
    {
        $aliases = [
            'name_en' => ['english_name', 'control_name_en', 'name_en'],
            'name_ur' => ['urdu_name', 'control_name_ur', 'name_ur'],
            'value_en' => ['english_value', 'value_en', 'description_en'],
            'value_ur' => ['urdu_value', 'value_ur', 'description_ur'],
        ];

        $normalized = [];

        foreach ($aliases as $target => $keys) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $payload) && filled($payload[$key])) {
                    $normalized[$target] = is_string($payload[$key]) ? trim($payload[$key]) : $payload[$key];
                    break;
                }
            }
        }

        $normalized['ai_status'] = CategoryAiStatus::Enriched->value;

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    private function mergeKnownValues(
        array $parsed,
        string $nameEn,
        string $nameUr,
        string $valueEn,
        string $valueUr,
    ): array {
        if (filled($nameEn)) {
            $parsed['name_en'] = $nameEn;
        }

        if (filled($nameUr)) {
            $parsed['name_ur'] = $nameUr;
        }

        if (filled($valueEn)) {
            $parsed['value_en'] = $valueEn;
        }

        if (filled($valueUr)) {
            $parsed['value_ur'] = $valueUr;
        }

        return $parsed;
    }
}
