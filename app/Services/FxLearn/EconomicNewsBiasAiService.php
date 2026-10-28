<?php

namespace App\Services\FxLearn;

use App\Enums\AiLogType;
use App\Models\AiConfiguration;
use App\Services\Ai\AiConfigurationService;
use App\Services\Ai\AiLogService;
use App\Services\Ai\OpenRouterClient;
use App\Support\FriendlyError;
use Throwable;

class EconomicNewsBiasAiService
{
    public function __construct(
        private readonly AiConfigurationService $ai,
        private readonly OpenRouterClient $openRouter,
        private readonly AiLogService $logger,
    ) {}

    /**
     * @param  array{title: string, ccy: string, impact: string, previous: string, forecast: string, actual: string}  $event
     * @return array{success: bool, message: string, data?: array{bias: string, verdict: string, summary_en: string, summary_ur: string}}
     */
    public function analyze(array $event): array
    {
        $configuration = $this->ai->active();

        if ($configuration === null || ! $configuration->isConnected()) {
            return [
                'success' => false,
                'message' => __('agricart.pages.fx-learn.ai_no_configuration'),
            ];
        }

        $title = trim((string) ($event['title'] ?? ''));
        $ccy = strtoupper(trim((string) ($event['ccy'] ?? '')));
        $actual = trim((string) ($event['actual'] ?? ''));

        if ($title === '' || $ccy === '') {
            return [
                'success' => false,
                'message' => __('agricart.pages.fx-learn.ai_event_required'),
            ];
        }

        if ($actual === '' || $actual === '—') {
            return [
                'success' => false,
                'message' => __('agricart.pages.fx-learn.ai_actual_required'),
            ];
        }

        $prompt = $this->buildPrompt($event);
        $start = microtime(true);

        try {
            $result = $this->openRouter->sendChat(
                (string) $configuration->api_key,
                $configuration->model_id,
                [
                    ['role' => 'system', 'content' => 'Reply with JSON only. No markdown fences.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                500,
                true,
            );

            $this->logger->fromConfiguration(
                $configuration,
                AiLogType::EconomicNewsBias,
                'economic_news_bias',
                (bool) ($result['success'] ?? false),
                $prompt,
                $result['response'] ?? null,
                ($result['success'] ?? false) ? null : ($result['message'] ?? null),
                $result['http_status'] ?? null,
                (int) ((microtime(true) - $start) * 1000),
            );

            if (! ($result['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => (string) ($result['message'] ?? __('agricart.pages.fx-learn.ai_invalid_json')),
                ];
            }

            $data = $this->parseJson((string) ($result['response'] ?? ''));

            if ($data === null) {
                return [
                    'success' => false,
                    'message' => __('agricart.pages.fx-learn.ai_invalid_json'),
                ];
            }

            return [
                'success' => true,
                'message' => __('agricart.pages.fx-learn.ai_ok'),
                'data' => $data,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => FriendlyError::message($e),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function buildPrompt(array $event): string
    {
        $title = (string) ($event['title'] ?? '');
        $ccy = strtoupper((string) ($event['ccy'] ?? ''));
        $impact = (string) ($event['impact'] ?? 'med');
        $previous = (string) ($event['previous'] ?? '—');
        $forecast = (string) ($event['forecast'] ?? '—');
        $actual = (string) ($event['actual'] ?? '—');

        return <<<PROMPT
You are an FX education assistant for Agricart FX Learn.
Analyze one economic release for students. Not financial advice.

Return ONLY valid JSON:
{
  "bias": "bullish"|"bearish"|"neutral",
  "verdict": "true_move"|"fakeout_liquidity_trap"|"unclear",
  "summary_en": "1-2 short sentences",
  "summary_ur": "1-2 short Urdu sentences"
}

Event:
- title: {$title}
- currency: {$ccy}
- impact: {$impact}
- previous: {$previous}
- forecast: {$forecast}
- actual: {$actual}

Bias = expected near-term reaction for {$ccy} (bullish = currency strength).
PROMPT;
    }

    /**
     * @return array{bias: string, verdict: string, summary_en: string, summary_ur: string}|null
     */
    private function parseJson(string $raw): ?array
    {
        $raw = trim($raw);
        $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw) ?? $raw;
        $raw = preg_replace('/\s*```$/', '', $raw) ?? $raw;

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return null;
        }

        $bias = strtolower((string) ($decoded['bias'] ?? ''));
        $verdict = strtolower((string) ($decoded['verdict'] ?? ''));

        if (! in_array($bias, ['bullish', 'bearish', 'neutral'], true)) {
            return null;
        }

        if (! in_array($verdict, ['true_move', 'fakeout_liquidity_trap', 'unclear'], true)) {
            return null;
        }

        return [
            'bias' => $bias,
            'verdict' => $verdict,
            'summary_en' => trim((string) ($decoded['summary_en'] ?? '')),
            'summary_ur' => trim((string) ($decoded['summary_ur'] ?? '')),
        ];
    }
}
