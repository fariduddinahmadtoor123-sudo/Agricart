<?php

namespace App\Services\FxLearn;

use App\Enums\AiLogType;
use App\Models\AiConfiguration;
use App\Services\Ai\AiConfigurationService;
use App\Services\Ai\OpenRouterClient;

class EconomicNewsBiasAiService
{
    public function __construct(
        private readonly AiConfigurationService $ai,
        private readonly OpenRouterClient $openRouter,
    ) {}

    /**
     * @param  array{
     *     title: string,
     *     ccy: string,
     *     impact: string,
     *     previous: string,
     *     forecast: string,
     *     actual: string,
     *     actual_source: 'feed'|'manual'
     * }  $event
     * @return array{
     *     success: bool,
     *     message: string,
     *     data?: array{
     *         bias: string,
     *         verdict: string,
     *         summary_en: string,
     *         summary_ur: string,
     *         dollar_bias?: string|null,
     *         inverted_note?: string|null
     *     }
     * }
     */
    public function analyze(array $event): array
    {
        $configuration = $this->ai->active();

        if ($configuration === null) {
            return [
                'success' => false,
                'message' => __('agricart.pages.fx-learn.econ_ai_no_configuration'),
            ];
        }

        if ($configuration->isPaused()) {
            return [
                'success' => false,
                'message' => __('agricart.ai.messages.paused'),
            ];
        }

        $title = trim((string) ($event['title'] ?? ''));
        $ccy = strtoupper(trim((string) ($event['ccy'] ?? '')));
        $actual = trim((string) ($event['actual'] ?? ''));

        if ($title === '' || $ccy === '') {
            return [
                'success' => false,
                'message' => __('agricart.pages.fx-learn.econ_ai_event_required'),
            ];
        }

        if ($actual === '' || $actual === '—') {
            return [
                'success' => false,
                'message' => __('agricart.pages.fx-learn.econ_ai_actual_required'),
            ];
        }

        $prompt = $this->buildPrompt($event);
        $result = $this->callAi($configuration, $prompt);

        if (! $result['success']) {
            return [
                'success' => false,
                'message' => $result['message'],
            ];
        }

        $parsed = $this->parseResponse((string) ($result['response'] ?? ''));

        if ($parsed === null) {
            return [
                'success' => false,
                'message' => __('agricart.pages.fx-learn.econ_ai_invalid_json'),
            ];
        }

        return [
            'success' => true,
            'message' => __('agricart.pages.fx-learn.econ_ai_ok'),
            'data' => $parsed,
        ];
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function buildPrompt(array $event): string
    {
        $title = trim((string) $event['title']);
        $ccy = strtoupper(trim((string) $event['ccy']));
        $impact = trim((string) ($event['impact'] ?? 'med'));
        $previous = $this->dash($event['previous'] ?? null);
        $forecast = $this->dash($event['forecast'] ?? null);
        $actual = $this->dash($event['actual'] ?? null);
        $source = (($event['actual_source'] ?? 'feed') === 'manual') ? 'manual_override' : 'json_feed';
        $isUsd = $ccy === 'USD';

        $invertBlock = $isUsd
            ? <<<'USD'
HARD RULE — USD news inverted assets (mandatory):
- First state the usual USD bias (bullish/bearish/neutral for the US dollar).
- Then you MUST also give an INVERTED read for gold (XAUUSD) and major US indices (e.g. US30 / NAS100 style):
  * Stronger-than-expected USD news → typically bearish gold / softer indices (risk-off or USD strength).
  * Weaker-than-expected USD news → typically bullish gold / firmer indices.
- Put dollar bias in "dollar_bias" and one short inverted sentence in "inverted_note".
- "bias" field = bias for the news currency (USD). Do not pretend gold moves the same way as USD.
USD
            : <<<'OTHER'
Currency focus:
- "bias" = educational reaction bias for the printed currency (and its common FX pairs).
- "dollar_bias" and "inverted_note" must be null unless the currency is USD.
OTHER;

        return <<<PROMPT
You are an FX Learn classroom assistant for Agricart (Pakistan). Educational market-reaction framing only — NOT financial advice, NOT a trade signal, NOT personalized recommendations.

Event:
- title: {$title}
- currency: {$ccy}
- impact: {$impact}
- previous: {$previous}
- forecast: {$forecast}
- actual: {$actual}
- actual_source: {$source}

Compare Actual vs Forecast (and Previous if useful). Keep the answer short and clear for students.

{$invertBlock}

Return ONLY valid JSON (no markdown) with this exact shape:
{
  "bias": "bullish|bearish|neutral",
  "verdict": "true_move|fakeout_liquidity_trap|unclear",
  "summary_en": "1-2 short sentences in English",
  "summary_ur": "1-2 short sentences in Urdu",
  "dollar_bias": "bullish|bearish|neutral|null",
  "inverted_note": "one short English sentence for gold/indices when currency is USD, else null"
}

Definitions:
- bias: likely near-term directional lean for the event currency after the print.
- verdict: true_move = print likely supports a clean follow-through; fakeout_liquidity_trap = spike/fade risk or trap-prone reaction; unclear = mixed/insufficient.
PROMPT;
    }

    /**
     * @return array{success: bool, message: string, response?: string|null, http_status?: int|null}
     */
    private function callAi(AiConfiguration $configuration, string $prompt): array
    {
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
            400,
            true,
        );

        app(\App\Services\Ai\AiLogService::class)->fromConfiguration(
            $configuration,
            AiLogType::EconomicNewsBias,
            'economic_news_bias',
            $result['success'],
            $prompt,
            $result['response'],
            $result['success'] ? null : $result['message'],
            $result['http_status'] ?? null,
            (int) ((microtime(true) - $start) * 1000),
        );

        return $result;
    }

    /**
     * @return array{
     *     bias: string,
     *     verdict: string,
     *     summary_en: string,
     *     summary_ur: string,
     *     dollar_bias: string|null,
     *     inverted_note: string|null
     * }|null
     */
    public function parseResponse(string $response): ?array
    {
        $json = trim($response);

        if (str_starts_with($json, '```')) {
            $json = preg_replace('/^```(?:json)?\s*/i', '', $json) ?? $json;
            $json = preg_replace('/\s*```$/', '', $json) ?? $json;
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded) && preg_match('/\{[\s\S]*\}/', $response, $matches) === 1) {
            $decoded = json_decode($matches[0], true);
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            return null;
        }

        $bias = strtolower(trim((string) ($decoded['bias'] ?? '')));
        $verdict = strtolower(trim((string) ($decoded['verdict'] ?? '')));
        $summaryEn = trim((string) ($decoded['summary_en'] ?? ''));
        $summaryUr = trim((string) ($decoded['summary_ur'] ?? ''));

        if (! in_array($bias, ['bullish', 'bearish', 'neutral'], true)) {
            return null;
        }

        if (! in_array($verdict, ['true_move', 'fakeout_liquidity_trap', 'unclear'], true)) {
            return null;
        }

        if ($summaryEn === '' || mb_strlen($summaryEn) > 800) {
            return null;
        }

        if ($summaryUr === '') {
            $summaryUr = $summaryEn;
        }

        if (mb_strlen($summaryUr) > 800) {
            return null;
        }

        $dollarBias = $decoded['dollar_bias'] ?? null;
        $dollarBias = is_string($dollarBias) ? strtolower(trim($dollarBias)) : null;

        if ($dollarBias === 'null' || $dollarBias === '') {
            $dollarBias = null;
        }

        if ($dollarBias !== null && ! in_array($dollarBias, ['bullish', 'bearish', 'neutral'], true)) {
            $dollarBias = null;
        }

        $inverted = $decoded['inverted_note'] ?? null;
        $inverted = is_string($inverted) ? trim($inverted) : null;

        if ($inverted === '' || strtolower((string) $inverted) === 'null') {
            $inverted = null;
        }

        if ($inverted !== null && mb_strlen($inverted) > 400) {
            $inverted = mb_substr($inverted, 0, 400);
        }

        return [
            'bias' => $bias,
            'verdict' => $verdict,
            'summary_en' => $summaryEn,
            'summary_ur' => $summaryUr,
            'dollar_bias' => $dollarBias,
            'inverted_note' => $inverted,
        ];
    }

    private function dash(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        $s = trim((string) $value);

        return $s !== '' ? $s : '—';
    }
}
