<?php

namespace App\Services\Catalog;

use App\Enums\AiLogType;
use App\Enums\CategoryAiStatus;
use App\Models\AiConfiguration;
use App\Services\Ai\AiConfigurationService;
use App\Services\Ai\OpenRouterClient;

class UnitEnrichmentService
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
        string $abbreviationEn,
        string $abbreviationUr,
    ): array {
        $configuration = $this->ai->active();

        if ($configuration === null) {
            return [
                'success' => false,
                'message' => __('agricart.units.ai.no_configuration'),
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
                'message' => __('agricart.units.ai.name_required'),
            ];
        }

        $known = array_filter([
            'name_en' => trim($nameEn) ?: null,
            'name_ur' => trim($nameUr) ?: null,
            'abbreviation_en' => trim($abbreviationEn) ?: null,
            'abbreviation_ur' => trim($abbreviationUr) ?: null,
        ]);

        $prompt = __('agricart.units.ai.prompt', [
            'name_en' => $known['name_en'] ?? '—',
            'name_ur' => $known['name_ur'] ?? '—',
            'abbreviation_en' => $known['abbreviation_en'] ?? '—',
            'abbreviation_ur' => $known['abbreviation_ur'] ?? '—',
        ])
            . "\n\nReturn ONLY valid JSON (no markdown) with keys: name_en, name_ur, abbreviation_en, abbreviation_ur. "
            . 'Use standard retail/hardware measurement units. Abbreviations should be short (1–6 characters when possible).';

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
            1024,
        );

        $this->logPrompt($configuration, $prompt, $result, (int) ((microtime(true) - $start) * 1000));

        if (! $result['success']) {
            return $result;
        }

        $parsed = $this->parseResponse((string) $result['response']);

        if ($parsed === null) {
            return [
                'success' => false,
                'message' => __('agricart.units.ai.invalid_json'),
            ];
        }

        return [
            'success' => true,
            'message' => __('agricart.units.ai.enriched'),
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
            AiLogType::UnitEnrichment,
            'unit_form',
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
        $limits = ['name_en' => 128, 'name_ur' => 128, 'abbreviation_en' => 32, 'abbreviation_ur' => 32];

        foreach (['name_en', 'name_ur', 'abbreviation_en', 'abbreviation_ur'] as $key) {
            if (isset($payload[$key]) && is_string($payload[$key]) && filled(trim($payload[$key]))) {
                $value = trim($payload[$key]);
                if (mb_strlen($value) > $limits[$key]) {
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
}
