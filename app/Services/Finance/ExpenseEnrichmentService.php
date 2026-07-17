<?php

namespace App\Services\Finance;

use App\Enums\AiLogType;
use App\Models\AiConfiguration;
use App\Services\Ai\AiConfigurationService;
use App\Services\Ai\AiLogService;
use App\Services\Ai\OpenRouterClient;

class ExpenseEnrichmentService
{
    public function __construct(
        private readonly AiConfigurationService $ai,
        private readonly OpenRouterClient $openRouter,
    ) {}

    public static function defaultPrompt(): string
    {
        return (string) __('agricart.finance.expenses.ai.default_prompt');
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function enrich(string $englishName, ?string $customPrompt = null): array
    {
        $configuration = $this->ai->active();

        if ($configuration === null) {
            return [
                'success' => false,
                'message' => __('agricart.finance.expenses.ai.no_configuration'),
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
        $fullPrompt = $prompt."\n\nEnglish expense name: ".$englishName
            ."\n\nReturn JSON only with keys: name_en, name_ur, information.";

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

        if (! $result['success']) {
            return $result;
        }

        $parsed = $this->parseResponse((string) $result['response']);

        if ($parsed === null) {
            return [
                'success' => false,
                'message' => __('agricart.finance.expenses.ai.invalid_json'),
            ];
        }

        return [
            'success' => true,
            'message' => __('agricart.finance.expenses.ai.enriched'),
            'data' => $parsed,
        ];
    }

    /**
     * @param  array{success: bool, message: string, response: string|null, http_status?: int|null}  $result
     */
    private function logPrompt(AiConfiguration $configuration, string $prompt, array $result, int $durationMs): void
    {
        app(AiLogService::class)->fromConfiguration(
            $configuration,
            AiLogType::ExpenseEnrichment,
            'expense_form',
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
    private function parseResponse(string $response): ?array
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

        $normalized = [];

        foreach (['name_en', 'name_ur', 'information'] as $key) {
            if (array_key_exists($key, $decoded) && filled($decoded[$key])) {
                $normalized[$key] = is_string($decoded[$key]) ? trim($decoded[$key]) : $decoded[$key];
            }
        }

        return $normalized !== [] ? $normalized : null;
    }
}
