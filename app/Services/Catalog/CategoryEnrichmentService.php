<?php

namespace App\Services\Catalog;

use App\Enums\AiLogType;
use App\Enums\CategoryAiStatus;
use App\Models\AiConfiguration;
use App\Services\Ai\AiConfigurationService;
use App\Services\Ai\OpenRouterClient;
use Illuminate\Support\Str;

class CategoryEnrichmentService
{
    private const MAX_TOKENS = 8192;

    public function __construct(
        private readonly AiConfigurationService $ai,
        private readonly OpenRouterClient $openRouter,
    ) {}

    public static function defaultPrompt(): string
    {
        return (string) __('agricart.categories.ai.default_prompt');
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
                'message' => __('agricart.categories.ai.no_configuration'),
            ];
        }

        if (! $configuration->hasUsableApiKey()) {
            return [
                'success' => false,
                'message' => __('agricart.ai.messages.api_key_reenter'),
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
        $prompt .= "\n\nImportant: respond with a single valid JSON object only. Do not truncate fields.";

        $attempts = 0;
        $lastFailure = __('agricart.categories.ai.invalid_json');

        while ($attempts < 2) {
            $attempts++;
            $result = filled($imagePath) && is_readable($imagePath)
                ? $this->callWithImage($configuration, $prompt, $imagePath)
                : $this->callTextOnly($configuration, $prompt, $englishName);

            if (! $result['success']) {
                return $result;
            }

            $parsed = $this->parseResponse((string) $result['response']);

            if ($parsed !== null) {
                return [
                    'success' => true,
                    'message' => __('agricart.categories.ai.enriched'),
                    'data' => $parsed,
                ];
            }

            $lastFailure = __('agricart.categories.ai.invalid_json');
        }

        return [
            'success' => false,
            'message' => $lastFailure,
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
            self::MAX_TOKENS,
            true,
        );

        $this->logPrompt($configuration, $prompt, $result, (int) ((microtime(true) - $start) * 1000));

        // Some models reject response_format; fall back without JSON mode once.
        if (! $result['success'] && str_contains(mb_strtolower($result['message']), 'response_format')) {
            $start = microtime(true);
            $result = $this->openRouter->chatWithImage(
                $configuration->api_key,
                $configuration->model_id,
                $prompt,
                $base64,
                $mime,
                self::MAX_TOKENS,
                false,
            );
            $this->logPrompt($configuration, $prompt.' [fallback-no-json-mode]', $result, (int) ((microtime(true) - $start) * 1000));
        }

        return $result;
    }

    /**
     * @return array{success: bool, message: string, response?: string|null}
     */
    private function callTextOnly(AiConfiguration $configuration, string $prompt, string $englishName): array
    {
        if ($configuration->isPaused()) {
            return [
                'success' => false,
                'message' => __('agricart.ai.messages.paused'),
                'response' => null,
            ];
        }

        $fullPrompt = $prompt."\n\nEnglish category name: ".$englishName;

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
            self::MAX_TOKENS,
            true,
        );

        $this->logPrompt($configuration, $fullPrompt, $result, (int) ((microtime(true) - $start) * 1000));

        if (! $result['success'] && str_contains(mb_strtolower($result['message']), 'response_format')) {
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
                self::MAX_TOKENS,
                false,
            );
            $this->logPrompt($configuration, $fullPrompt.' [fallback-no-json-mode]', $result, (int) ((microtime(true) - $start) * 1000));
        }

        return $result;
    }

    /**
     * @param  array{success: bool, message: string, response: string|null, http_status?: int|null}  $result
     */
    private function logPrompt(AiConfiguration $configuration, string $prompt, array $result, int $durationMs): void
    {
        app(\App\Services\Ai\AiLogService::class)->fromConfiguration(
            $configuration,
            AiLogType::CategoryEnrichment,
            'category_form',
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
        $candidates = $this->jsonCandidates($response);

        foreach ($candidates as $candidate) {
            $decoded = json_decode($candidate, true);

            if (! is_array($decoded)) {
                $decoded = json_decode($this->repairTruncatedJson($candidate), true);
            }

            if (! is_array($decoded) || $decoded === []) {
                continue;
            }

            $normalized = $this->normalizePayload($decoded);

            if ($this->hasUsableContent($normalized)) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function jsonCandidates(string $response): array
    {
        $json = trim($response);
        $json = preg_replace('/^\xEF\xBB\xBF/', '', $json) ?? $json;

        if (str_starts_with($json, '```')) {
            $json = preg_replace('/^```(?:json)?\s*/i', '', $json) ?? $json;
            $json = preg_replace('/\s*```$/', '', $json) ?? $json;
            $json = trim($json);
        }

        $candidates = [$json];

        if (preg_match('/\{[\s\S]*\}/', $response, $matches) === 1) {
            $candidates[] = $matches[0];
        }

        return array_values(array_unique(array_filter($candidates, fn (string $value): bool => $value !== '')));
    }

    private function repairTruncatedJson(string $json): string
    {
        $json = rtrim($json);
        $json = preg_replace('/,\s*$/', '', $json) ?? $json;

        // Close an open string if truncation cut mid-value.
        $quoteCount = substr_count($json, '"') - substr_count($json, '\\"');
        if ($quoteCount % 2 === 1) {
            $json .= '"';
        }

        $json = preg_replace('/,\s*$/', '', $json) ?? $json;

        $opens = substr_count($json, '{') - substr_count($json, '}');
        $openArrays = substr_count($json, '[') - substr_count($json, ']');

        if ($openArrays > 0) {
            $json .= str_repeat(']', $openArrays);
        }

        if ($opens > 0) {
            $json .= str_repeat('}', $opens);
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hasUsableContent(array $payload): bool
    {
        foreach (['name_en', 'name_ur', 'short_description_en', 'seo_title_en', 'full_description_en'] as $key) {
            if (filled($payload[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizePayload(array $payload): array
    {
        $map = [
            'name_en', 'name_ur', 'slug', 'code',
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

        if (isset($payload['faqs']) && is_array($payload['faqs'])) {
            $normalized['faqs'] = collect($payload['faqs'])
                ->filter(fn ($faq): bool => is_array($faq))
                ->map(fn (array $faq): array => [
                    'question_en' => (string) ($faq['question_en'] ?? ''),
                    'question_ur' => (string) ($faq['question_ur'] ?? ''),
                    'answer_en' => (string) ($faq['answer_en'] ?? ''),
                    'answer_ur' => (string) ($faq['answer_ur'] ?? ''),
                ])
                ->values()
                ->all();
        }

        $normalized['ai_status'] = CategoryAiStatus::Enriched->value;

        if (! isset($normalized['slug']) && isset($normalized['name_en'])) {
            $normalized['slug'] = Str::slug((string) $normalized['name_en']);
        }

        return $normalized;
    }
}
