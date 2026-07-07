<?php

namespace App\Services\Settings;

use App\Enums\AiLogType;
use App\Enums\CategoryAiStatus;
use App\Models\AiConfiguration;
use App\Services\Ai\AiConfigurationService;
use App\Services\Ai\OpenRouterClient;

class CompanyProfileEnrichmentService
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
                'message' => __('company_profile.ai.no_configuration'),
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
                'message' => __('company_profile.ai.input_required'),
            ];
        }

        $prompt = __('company_profile.ai.prompt', [
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ])
            . "\n\nReturn ONLY valid JSON (no markdown) with the same structure. "
            . 'Fill empty English fields from Urdu and empty Urdu fields from English. '
            . 'Keep every provided field unchanged. Do not invent phone numbers, emails, or tax IDs. '
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
                'message' => __('company_profile.ai.invalid_json'),
            ];
        }

        $parsed = $this->mergeKnownValues($parsed, $payload);
        $parsed['ai_status'] = CategoryAiStatus::Enriched->value;

        return [
            'success' => true,
            'message' => __('company_profile.ai.enriched'),
            'data' => $parsed,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hasTranslatableInput(array $payload): bool
    {
        $scalarPairs = [
            ['business_name_en', 'business_name_ur'],
            ['tagline_en', 'tagline_ur'],
        ];

        foreach ($scalarPairs as [$en, $ur]) {
            if (filled($payload[$en] ?? null) || filled($payload[$ur] ?? null)) {
                return true;
            }
        }

        foreach (is_array($payload['phones'] ?? null) ? $payload['phones'] : [] as $phone) {
            if (! is_array($phone)) {
                continue;
            }

            foreach (['contact_person_en', 'contact_person_ur'] as $key) {
                if (filled($phone[$key] ?? null)) {
                    return true;
                }
            }
        }

        foreach (is_array($payload['addresses'] ?? null) ? $payload['addresses'] : [] as $address) {
            if (! is_array($address)) {
                continue;
            }

            foreach (['address_en', 'address_ur'] as $key) {
                if (filled($address[$key] ?? null)) {
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
            AiLogType::CompanyProfileEnrichment,
            'company_profile_form',
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
        foreach (['business_name_en', 'business_name_ur', 'tagline_en', 'tagline_ur'] as $key) {
            if (filled($known[$key] ?? null)) {
                $parsed[$key] = $known[$key];
            }
        }

        if (isset($known['phones']) && is_array($known['phones'])) {
            $parsed['phones'] = $this->mergeRowPairs(
                is_array($parsed['phones'] ?? null) ? $parsed['phones'] : [],
                $known['phones'],
                ['contact_person_en', 'contact_person_ur'],
            );
        }

        if (isset($known['addresses']) && is_array($known['addresses'])) {
            $parsed['addresses'] = $this->mergeRowPairs(
                is_array($parsed['addresses'] ?? null) ? $parsed['addresses'] : [],
                $known['addresses'],
                ['address_en', 'address_ur'],
            );
        }

        return $parsed;
    }

    /**
     * @param  list<array<string, mixed>>  $parsedRows
     * @param  list<array<string, mixed>>  $knownRows
     * @param  list<string>  $bilingualKeys
     * @return list<array<string, mixed>>
     */
    private function mergeRowPairs(array $parsedRows, array $knownRows, array $bilingualKeys): array
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

            if (array_key_exists('designation', $knownRow)) {
                $parsedRow['designation'] = $knownRow['designation'];
            }

            if (array_key_exists('phone_number', $knownRow)) {
                $parsedRow['phone_number'] = $knownRow['phone_number'];
            }

            if (array_key_exists('postal_code', $knownRow)) {
                $parsedRow['postal_code'] = $knownRow['postal_code'];
            }

            if (array_key_exists('is_primary', $knownRow)) {
                $parsedRow['is_primary'] = $knownRow['is_primary'];
            }

            $merged[] = $parsedRow;
        }

        return $merged;
    }
}
