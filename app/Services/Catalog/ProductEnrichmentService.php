<?php

namespace App\Services\Catalog;

use App\Enums\AiLogType;
use App\Enums\CategoryAiStatus;
use App\Models\AiConfiguration;
use App\Services\Ai\AiConfigurationService;
use App\Services\Ai\OpenRouterClient;
use Illuminate\Support\Str;

class ProductEnrichmentService
{
    public function __construct(
        private readonly AiConfigurationService $ai,
        private readonly OpenRouterClient $openRouter,
    ) {}

    public static function defaultPrompt(): string
    {
        return (string) __('agricart.products.ai.default_prompt');
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
                'message' => __('agricart.products.ai.no_configuration'),
            ];
        }

        if ($configuration->isPaused()) {
            return [
                'success' => false,
                'message' => __('agricart.ai.messages.paused'),
            ];
        }

        $prompt = filled($customPrompt) ? $customPrompt : self::defaultPrompt();
        $prompt = str_replace(':name', $englishName, $prompt);

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
                'message' => __('agricart.products.ai.invalid_json'),
            ];
        }

        // The user-entered product name is authoritative; image recognition must never replace it.
        $parsed['name_en'] = $englishName;

        return [
            'success' => true,
            'message' => __('agricart.products.ai.enriched'),
            'data' => $parsed,
            'model_id' => $configuration->model_id,
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
        $fullPrompt = $prompt . "\n\nEnglish product name: " . $englishName;

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
            4096,
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
            AiLogType::ProductEnrichment,
            'product_form',
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

        if (! is_array($decoded)) {
            if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
                $decoded = json_decode($matches[0], true);
            }
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
        $map = [
            'name_en', 'name_ur', 'slug', 'sku',
            'short_description_en', 'short_description_ur',
            'full_description_en', 'full_description_ur',
            'usage_guidelines_en', 'usage_guidelines_ur',
            'benefits_en', 'benefits_ur',
            'warnings_en', 'warnings_ur',
            'buying_guide_en', 'buying_guide_ur',
            'seo_title_en', 'seo_title_ur',
            'seo_description_en', 'seo_description_ur',
            'seo_keywords_en', 'seo_keywords_ur',
            'seo_focus_keyword_en', 'seo_focus_keyword_ur',
            'hs_code',
            'customs_notes_en', 'customs_notes_ur',
            'import_notes_en', 'import_notes_ur',
            'export_notes_en', 'export_notes_ur',
        ];

        $normalized = [];

        foreach ($map as $key) {
            if (array_key_exists($key, $payload) && filled($payload[$key])) {
                $normalized[$key] = is_string($payload[$key])
                    ? trim($payload[$key])
                    : $payload[$key];
            }
        }

        if (isset($payload['search_terms']) && is_array($payload['search_terms'])) {
            $normalized['search_terms'] = array_values(array_filter($payload['search_terms']));
        }

        $normalized['ai_status'] = CategoryAiStatus::Enriched->value;

        if (! isset($normalized['slug']) && isset($normalized['name_en'])) {
            $normalized['slug'] = Str::slug((string) $normalized['name_en']);
        }

        return $normalized;
    }
}
