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

        if (! filled($nameEn) && ! filled($nameUr) && ! filled($valueEn) && ! filled($valueUr)) {
            return [
                'success' => false,
                'message' => __('agricart.controls.ai.input_required'),
            ];
        }

        $hasEnglish = filled($nameEn) || filled($valueEn);
        $hasUrdu = filled($nameUr) || filled($valueUr);

        $direction = match (true) {
            $hasEnglish && ! $hasUrdu => __('agricart.controls.ai.direction_en_to_ur', [
                'fields' => $this->summarizeProvidedFields($nameEn, $valueEn),
            ]),
            $hasUrdu && ! $hasEnglish => __('agricart.controls.ai.direction_ur_to_en', [
                'fields' => $this->summarizeProvidedFields($nameUr, $valueUr),
            ]),
            default => __('agricart.controls.ai.direction_complete'),
        };

        $prompt = __('agricart.controls.ai.prompt', [
            'name_en' => filled($nameEn) ? $nameEn : '—',
            'name_ur' => filled($nameUr) ? $nameUr : '—',
            'value_en' => filled($valueEn) ? $valueEn : '—',
            'value_ur' => filled($valueUr) ? $valueUr : '—',
        ])
            . "\n\n" . $direction
            . "\n\nReturn ONLY valid JSON (no markdown) with keys: name_en, name_ur, value_en, value_ur. "
            . 'Only translate fields that are empty on the target language side. Keep every provided field unchanged. '
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

        $validationMessage = $this->validateTranslations($parsed, $nameEn, $nameUr, $valueEn, $valueUr);

        if ($validationMessage !== null) {
            return [
                'success' => false,
                'message' => $validationMessage,
            ];
        }

        return [
            'success' => true,
            'message' => __('agricart.controls.ai.enriched'),
            'data' => $parsed,
        ];
    }

    private function summarizeProvidedFields(string $name, string $value): string
    {
        $parts = [];

        if (filled($name)) {
            $parts[] = __('agricart.controls.ai.provided_name') . ': ' . $name;
        }

        if (filled($value)) {
            $parts[] = __('agricart.controls.ai.provided_value') . ': ' . $value;
        }

        return implode('; ', $parts);
    }

  /**
     * @param  array<string, mixed>  $parsed
     */
    private function validateTranslations(
        array $parsed,
        string $nameEn,
        string $nameUr,
        string $valueEn,
        string $valueUr,
    ): ?string {
        if (filled($nameEn) && ! filled($nameUr)) {
            $translated = trim((string) ($parsed['name_ur'] ?? ''));

            if (! filled($translated) || strcasecmp($translated, $nameEn) === 0) {
                return __('agricart.controls.ai.missing_ur');
            }
        }

        if (filled($nameUr) && ! filled($nameEn)) {
            $translated = trim((string) ($parsed['name_en'] ?? ''));

            if (! filled($translated)) {
                return __('agricart.controls.ai.missing_en');
            }
        }

        if (filled($valueEn) && ! filled($valueUr)) {
            $translated = trim((string) ($parsed['value_ur'] ?? ''));

            if (! filled($translated) || strcasecmp($translated, $valueEn) === 0) {
                return __('agricart.controls.ai.missing_ur');
            }
        }

        if (filled($valueUr) && ! filled($valueEn)) {
            $translated = trim((string) ($parsed['value_en'] ?? ''));

            if (! filled($translated)) {
                return __('agricart.controls.ai.missing_en');
            }
        }

        return null;
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
        $limits = [
            'name_en' => 128,
            'name_ur' => 128,
            'value_en' => 1000,
            'value_ur' => 1000,
        ];

        if (array_diff(array_keys($payload), array_keys($limits)) !== []) {
            return null;
        }

        $normalized = [];

        foreach ($payload as $key => $value) {
            if (! is_string($value) || ! filled(trim($value))) {
                return null;
            }

            $value = trim($value);

            if (mb_strlen($value) > $limits[$key]) {
                return null;
            }

            $normalized[$key] = $value;
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
