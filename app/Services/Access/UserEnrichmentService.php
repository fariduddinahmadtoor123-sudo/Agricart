<?php

namespace App\Services\Access;

use App\Enums\AiLogType;
use App\Enums\CategoryAiStatus;
use App\Models\AiConfiguration;
use App\Services\Ai\AiConfigurationService;
use App\Services\Ai\OpenRouterClient;

class UserEnrichmentService
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
                'message' => __('agricart.users.ai.no_configuration'),
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
                'message' => __('agricart.users.ai.input_required'),
            ];
        }

        $prompt = __('agricart.users.ai.prompt', [
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ])
            . "\n\nReturn ONLY valid JSON (no markdown) with keys: "
            . 'name_en, name_ur, address_en, address_ur, phone_rows, bank_rows. '
            . 'phone_rows is an array of objects with contact_person, contact_person_ur, phone_number. '
            . 'bank_rows is an array of objects with bank_name, account_title, account_title_ur, account_number. '
            . 'Fill empty English from Urdu and empty Urdu from English. Keep every provided value unchanged. '
            . 'Do not invent emails, phone numbers, IBANs, or bank names. '
            . 'Use Pakistani staff / agriculture retail context. Roman Urdu is acceptable in English address fields when needed.';

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
                'message' => __('agricart.users.ai.invalid_json'),
            ];
        }

        $parsed = $this->mergeKnownValues($parsed, $payload);
        $parsed['ai_status'] = CategoryAiStatus::Enriched->value;

        return [
            'success' => true,
            'message' => __('agricart.users.ai.enriched'),
            'data' => $parsed,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hasTranslatableInput(array $payload): bool
    {
        foreach (['name_en', 'name_ur', 'address_en', 'address_ur'] as $key) {
            if (filled($payload[$key] ?? null)) {
                return true;
            }
        }

        foreach ($payload['phone_rows'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            if (filled($row['contact_person'] ?? null) || filled($row['contact_person_ur'] ?? null)) {
                return true;
            }
        }

        foreach ($payload['bank_rows'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            if (filled($row['account_title'] ?? null) || filled($row['account_title_ur'] ?? null)) {
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
            AiLogType::UserEnrichment,
            'user_form',
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
        foreach (['name_en', 'name_ur', 'address_en', 'address_ur'] as $key) {
            if (filled($known[$key] ?? null)) {
                $parsed[$key] = $known[$key];
            }
        }

        return $parsed;
    }
}
