<?php

namespace App\Services\Contacts;

use App\Enums\AiLogType;
use App\Enums\CategoryAiStatus;
use App\Models\AiConfiguration;
use App\Services\Ai\AiConfigurationService;
use App\Services\Ai\OpenRouterClient;

class CustomerEnrichmentService
{
    public function __construct(
        private readonly AiConfigurationService $ai,
        private readonly OpenRouterClient $openRouter,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function enrich(array $payload): array
    {
        $configuration = $this->ai->active();

        if ($configuration === null) {
            return [
                'success' => false,
                'message' => __('customers.ai.no_configuration'),
            ];
        }

        if ($configuration->isPaused()) {
            return [
                'success' => false,
                'message' => __('agricart.ai.messages.paused'),
            ];
        }

        if (! $this->hasTranslatableInput($payload)) {
            return [
                'success' => false,
                'message' => __('customers.ai.input_required'),
            ];
        }

        $prompt = __('customers.ai.prompt', [
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ])
            . "\n\nReturn ONLY valid JSON (no markdown) with keys: "
            . 'name_en, name_ur, address_en, address_ur, city_en, city_ur. '
            . 'Use the display_name and address_quick fields to infer structured bilingual customer records. '
            . 'Write searchable Roman Urdu / English address text suitable for retail customer lookup in Pakistan. '
            . 'Keep every provided field unchanged when already filled. Do not invent phone numbers or emails. '
            . 'Do not change city_en or city_ur when they are already provided.';

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
            2048,
        );

        $this->logPrompt($configuration, $prompt, $result, (int) ((microtime(true) - $start) * 1000));

        if (! $result['success']) {
            return $result;
        }

        $parsed = $this->parseResponse((string) $result['response']);

        if ($parsed === null) {
            return [
                'success' => false,
                'message' => __('customers.ai.invalid_json'),
            ];
        }

        $parsed = $this->mergeKnownValues($parsed, $payload);
        $parsed['ai_status'] = CategoryAiStatus::Enriched->value;

        return [
            'success' => true,
            'message' => __('customers.ai.enriched'),
            'data' => $parsed,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hasTranslatableInput(array $payload): bool
    {
        foreach ([
            'display_name',
            'name_en',
            'name_ur',
            'address_quick',
            'address_en',
            'address_ur',
            'city_en',
            'city_ur',
        ] as $key) {
            if (filled($payload[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{success: bool, message: string, response: string|null, http_status?: int|null}  $result
     */
    private function logPrompt(AiConfiguration $configuration, string $prompt, array $result, int $durationMs): void
    {
        app(\App\Services\Ai\AiLogService::class)->fromConfiguration(
            $configuration,
            AiLogType::CustomerEnrichment,
            'customer_form',
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

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  array<string, mixed>  $known
     * @return array<string, mixed>
     */
    private function mergeKnownValues(array $parsed, array $known): array
    {
        foreach ([
            'name_en',
            'name_ur',
            'address_en',
            'address_ur',
            'city_en',
            'city_ur',
        ] as $key) {
            if (filled($known[$key] ?? null)) {
                $parsed[$key] = $known[$key];
            }
        }

        return $parsed;
    }
}
