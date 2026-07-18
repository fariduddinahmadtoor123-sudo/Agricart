<?php

namespace App\Services\Catalog;

use App\Enums\AiLogType;
use App\Enums\CategoryAiStatus;
use App\Models\AiConfiguration;
use App\Services\Ai\AiConfigurationService;
use App\Services\Ai\OpenRouterClient;

class BrandEnrichmentService
{
    public function __construct(
        private readonly AiConfigurationService $ai,
        private readonly OpenRouterClient $openRouter,
    ) {}

    public static function defaultPrompt(): string
    {
        return (string) __('agricart.brands.ai.default_prompt');
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function enrich(
        string $englishName,
        ?string $imagePath,
        ?string $customPrompt = null,
    ): array {
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

        if (! filled(trim((string) $customPrompt))) {
            return [
                'success' => false,
                'message' => __('agricart.brands.ai.prompt_required'),
            ];
        }

        $base = str_replace(':name', $englishName, trim((string) $customPrompt));

        $prompt = $base . "\n\nReturn ONLY valid JSON (no markdown) with keys: name_ur, description_en, description_ur, slug. Write professional, customer-facing brand descriptions in English and Urdu suitable for an online agriculture store.";

        $result = filled($imagePath) && is_readable($imagePath)
            ? $this->callWithImage($configuration, $prompt, $imagePath)
            : $this->callTextOnly($configuration, $prompt, $englishName);

        if (! $result['success']) {
            return $result;
        }

        $parsed = $this->parseResponse((string) $result['response']);

        if ($parsed === null) {
            return [
                'success' => false,
                'message' => __('agricart.brands.ai.invalid_json'),
            ];
        }

        return [
            'success' => true,
            'message' => __('agricart.brands.ai.enriched'),
            'data' => $parsed,
        ];
    }

    /**
     * @return array{success: bool, message: string, response?: string|null}
     */
    private function callWithImage(AiConfiguration $configuration, string $prompt, string $imagePath): array
    {
        $mime = mime_content_type($imagePath) ?: 'image/jpeg';
        $base64 = base64_encode((string) file_get_contents($imagePath));

        $start = microtime(true);
        $result = $this->openRouter->chatWithImage(
            $configuration->api_key,
            $configuration->model_id,
            $prompt,
            $base64,
            $mime,
        );

        $this->logPrompt($configuration, $prompt, $result, (int) ((microtime(true) - $start) * 1000));

        return $result;
    }

    /**
     * @return array{success: bool, message: string, response?: string|null}
     */
    private function callTextOnly(AiConfiguration $configuration, string $prompt, string $englishName): array
    {
        $fullPrompt = $prompt . "\n\nEnglish brand name: " . $englishName;

        $start = microtime(true);
        $result = $this->openRouter->sendChat(
            $configuration->api_key,
            $configuration->model_id,
            [
                [
                    'role' => 'user',
                    'content' => $fullPrompt,
                ],
            ],
            2048,
        );

        $this->logPrompt($configuration, $fullPrompt, $result, (int) ((microtime(true) - $start) * 1000));

        return $result;
    }

    /**
     * @param  array{success: bool, message: string, response: string|null, http_status?: int|null}  $result
     */
    private function logPrompt(AiConfiguration $configuration, string $prompt, array $result, int $durationMs): void
    {
        app(\App\Services\Ai\AiLogService::class)->fromConfiguration(
            $configuration,
            AiLogType::BrandEnrichment,
            'brand_form',
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
        $limits = [
            'name_ur' => 255,
            'slug' => 255,
            'description_en' => 20_000,
            'description_ur' => 20_000,
        ];

        foreach (['name_ur', 'description_en', 'description_ur', 'slug'] as $key) {
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
