<?php

namespace App\Services\Contacts;

use App\Enums\AiLogType;
use App\Enums\CategoryAiStatus;
use App\Models\AiConfiguration;
use App\Services\Ai\AiConfigurationService;
use App\Services\Ai\OpenRouterClient;

class SupplierEnrichmentService
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
                'message' => __('suppliers.ai.no_configuration'),
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
                'message' => __('suppliers.ai.input_required'),
            ];
        }

        $prompt = __('suppliers.ai.prompt', [
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ])
            . "\n\nReturn ONLY valid JSON (no markdown) with the same structure. "
            . 'Fill empty English fields from Urdu and empty Urdu fields from English. '
            . 'Keep every provided field unchanged. Do not invent phone numbers, emails, IBANs, or bank names. '
            . 'Do not change country_ur, city_en, or city_ur when they are already provided. '
            . 'Use agriculture and hardware retail terminology in Pakistan.';

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
                'message' => __('suppliers.ai.invalid_json'),
            ];
        }

        $parsed = $this->mergeKnownValues($parsed, $payload);
        $parsed['ai_status'] = CategoryAiStatus::Enriched->value;

        return [
            'success' => true,
            'message' => __('suppliers.ai.enriched'),
            'data' => $parsed,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hasTranslatableInput(array $payload): bool
    {
        foreach ([
            ['business_name_en', 'business_name_ur'],
            ['address_en', 'address_ur'],
            ['city_en', 'city_ur'],
        ] as [$en, $ur]) {
            if (filled($payload[$en] ?? null) || filled($payload[$ur] ?? null)) {
                return true;
            }
        }

        foreach (is_array($payload['contacts'] ?? null) ? $payload['contacts'] : [] as $contact) {
            if (! is_array($contact)) {
                continue;
            }

            foreach (['contact_name_en', 'contact_name_ur'] as $key) {
                if (filled($contact[$key] ?? null)) {
                    return true;
                }
            }
        }

        foreach (is_array($payload['bank_accounts'] ?? null) ? $payload['bank_accounts'] : [] as $bank) {
            if (! is_array($bank)) {
                continue;
            }

            foreach (['account_title', 'account_title_ur'] as $key) {
                if (filled($bank[$key] ?? null)) {
                    return true;
                }
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
            AiLogType::SupplierEnrichment,
            'supplier_form',
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
            'business_name_en',
            'business_name_ur',
            'address_en',
            'address_ur',
            'country_ur',
            'city_en',
            'city_ur',
        ] as $key) {
            if (filled($known[$key] ?? null)) {
                $parsed[$key] = $known[$key];
            }
        }

        if (isset($known['contacts']) && is_array($known['contacts'])) {
            $parsed['contacts'] = $this->mergeRowPairs(
                is_array($parsed['contacts'] ?? null) ? $parsed['contacts'] : [],
                $known['contacts'],
                ['contact_name_en', 'contact_name_ur'],
                ['mobile_number'],
            );
        }

        if (isset($known['bank_accounts']) && is_array($known['bank_accounts'])) {
            $parsed['bank_accounts'] = $this->mergeRowPairs(
                is_array($parsed['bank_accounts'] ?? null) ? $parsed['bank_accounts'] : [],
                $known['bank_accounts'],
                ['account_title', 'account_title_ur'],
                ['bank_key', 'bank_name_en', 'bank_name_ur', 'account_number'],
            );
        }

        return $parsed;
    }

    /**
     * @param  list<array<string, mixed>>  $parsedRows
     * @param  list<array<string, mixed>>  $knownRows
     * @param  list<string>  $bilingualKeys
     * @param  list<string>  $preserveKeys
     * @return list<array<string, mixed>>
     */
    private function mergeRowPairs(array $parsedRows, array $knownRows, array $bilingualKeys, array $preserveKeys = []): array
    {
        $merged = [];

        foreach ($knownRows as $index => $knownRow) {
            if (! is_array($knownRow)) {
                continue;
            }

            $parsedRow = is_array($parsedRows[$index] ?? null) ? $parsedRows[$index] : $knownRow;

            foreach ($bilingualKeys as $key) {
                if (filled($knownRow[$key] ?? null)) {
                    $parsedRow[$key] = $knownRow[$key];
                }
            }

            foreach ($preserveKeys as $key) {
                if (array_key_exists($key, $knownRow)) {
                    $parsedRow[$key] = $knownRow[$key];
                }
            }

            $merged[] = $parsedRow;
        }

        return $merged;
    }
}
