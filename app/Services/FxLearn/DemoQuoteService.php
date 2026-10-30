<?php

namespace App\Services\FxLearn;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DemoQuoteService
{
    /**
     * @return array<string, array{
     *   bid: float,
     *   ask: float,
     *   mid: float,
     *   high: float,
     *   low: float,
     *   live: bool,
     *   digits: int,
     *   label: string,
     *   category: string,
     *   contract: float,
     *   tv: string,
     *   quoted_at: string|null
     * }>
     */
    public function snapshot(): array
    {
        return Cache::remember('fxlearn.demo.quotes.v3', 20, function (): array {
            return $this->fetchFresh();
        });
    }

    /**
     * @return array<string, array{
     *   bid: float,
     *   ask: float,
     *   mid: float,
     *   high: float,
     *   low: float,
     *   live: bool,
     *   digits: int,
     *   label: string,
     *   category: string,
     *   contract: float,
     *   tv: string,
     *   quoted_at: string|null
     * }>
     */
    public function refresh(): array
    {
        Cache::forget('fxlearn.demo.quotes.v3');

        return $this->snapshot();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function fetchFresh(): array
    {
        $catalog = DemoSymbolCatalog::all();
        $ticks = $this->fetchAllTicks($catalog);
        $out = [];

        foreach ($catalog as $key => $meta) {
            $tick = $ticks[$key] ?? null;
            $live = is_array($tick);
            $price = $live ? (float) $tick['mid'] : (float) $meta['seed'];
            $half = $this->halfSpread($key, $price);
            $digits = (int) $meta['digits'];
            $high = $live && isset($tick['high']) ? (float) $tick['high'] : round($price * 1.002, $digits);
            $low = $live && isset($tick['low']) ? (float) $tick['low'] : round($price * 0.998, $digits);

            if ($high < $low) {
                [$high, $low] = [$low, $high];
            }

            $out[$key] = [
                'label' => $meta['label'],
                'category' => $meta['category'],
                'digits' => $digits,
                'contract' => (float) $meta['contract'],
                'tv' => (string) $meta['tv'],
                'mid' => round($price, $digits),
                'bid' => round($price - $half, $digits),
                'ask' => round($price + $half, $digits),
                'high' => round($high, $digits),
                'low' => round($low, $digits),
                'live' => $live,
                'quoted_at' => $live ? ($tick['quoted_at'] ?? now()->toIso8601String()) : null,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, array<string, mixed>>  $catalog
     * @return array<string, array{mid: float, high?: float, low?: float, quoted_at?: string}>
     */
    private function fetchAllTicks(array $catalog): array
    {
        $ticks = [];

        try {
            $responses = Http::pool(function ($pool) use ($catalog) {
                $requests = [];

                foreach ($catalog as $key => $meta) {
                    if (($meta['feed'] ?? '') === 'yahoo') {
                        $url = 'https://query1.finance.yahoo.com/v8/finance/chart/'
                            .rawurlencode((string) $meta['yahoo'])
                            .'?interval=1m&range=1d';
                        $requests[$key] = $pool->as($key)
                            ->timeout(8)
                            ->withHeaders([
                                'User-Agent' => 'Mozilla/5.0 (compatible; Agricart-FXLearn/1.0)',
                                'Accept' => 'application/json',
                            ])
                            ->get($url);
                    }

                    if (($meta['feed'] ?? '') === 'binance') {
                        $requests[$key] = $pool->as($key)
                            ->timeout(8)
                            ->withHeaders(['User-Agent' => 'Agricart-FXLearn/1.0'])
                            ->get('https://api.binance.com/api/v3/ticker/24hr', [
                                'symbol' => (string) $meta['binance'],
                            ]);
                    }
                }

                return $requests;
            });

            foreach ($catalog as $key => $meta) {
                $response = $responses[$key] ?? null;

                if (! is_object($response) || ! method_exists($response, 'successful') || ! $response->successful()) {
                    continue;
                }

                if (($meta['feed'] ?? '') === 'binance') {
                    $parsed = $this->parseBinancePayload($response->json());

                    if ($parsed !== null) {
                        $ticks[$key] = $parsed;
                    }

                    continue;
                }

                $parsed = $this->parseYahooPayload($response->json());

                if ($parsed !== null) {
                    $ticks[$key] = $parsed;
                }
            }
        } catch (Throwable $e) {
            Log::warning('FX Learn quote pool failed', ['error' => $e->getMessage()]);
        }

        return $ticks;
    }

    /**
     * @param  mixed  $json
     * @return array{mid: float, high: float, low: float, quoted_at: string}|null
     */
    private function parseYahooPayload(mixed $json): ?array
    {
        if (! is_array($json)) {
            return null;
        }

        $result = data_get($json, 'chart.result.0');

        if (! is_array($result)) {
            return null;
        }

        $meta = $result['meta'] ?? [];
        $price = (float) ($meta['regularMarketPrice'] ?? 0);

        if ($price <= 0) {
            $price = (float) ($meta['previousClose'] ?? 0);
        }

        if ($price <= 0) {
            $closes = data_get($result, 'indicators.quote.0.close', []);

            if (is_array($closes)) {
                for ($i = count($closes) - 1; $i >= 0; $i--) {
                    if (is_numeric($closes[$i]) && (float) $closes[$i] > 0) {
                        $price = (float) $closes[$i];
                        break;
                    }
                }
            }
        }

        if ($price <= 0) {
            return null;
        }

        $high = (float) ($meta['regularMarketDayHigh'] ?? 0);
        $low = (float) ($meta['regularMarketDayLow'] ?? 0);

        if ($high <= 0 || $low <= 0) {
            $highs = data_get($result, 'indicators.quote.0.high', []);
            $lows = data_get($result, 'indicators.quote.0.low', []);
            $high = $this->extremeFromSeries($highs, true) ?? $price;
            $low = $this->extremeFromSeries($lows, false) ?? $price;
        }

        $ts = (int) ($meta['regularMarketTime'] ?? data_get($result, 'timestamp.'.(count((array) data_get($result, 'timestamp', [])) - 1), 0));
        $quotedAt = $ts > 0
            ? \Carbon\CarbonImmutable::createFromTimestamp($ts, 'UTC')->setTimezone(agricart_display_timezone())->toIso8601String()
            : now(agricart_display_timezone())->toIso8601String();

        return [
            'mid' => $price,
            'high' => $high > 0 ? $high : $price,
            'low' => $low > 0 ? $low : $price,
            'quoted_at' => $quotedAt,
        ];
    }

    /**
     * @param  mixed  $json
     * @return array{mid: float, high: float, low: float, quoted_at: string}|null
     */
    private function parseBinancePayload(mixed $json): ?array
    {
        if (! is_array($json)) {
            return null;
        }

        $last = (float) ($json['lastPrice'] ?? 0);
        $bid = (float) ($json['bidPrice'] ?? 0);
        $ask = (float) ($json['askPrice'] ?? 0);
        $mid = $last > 0 ? $last : (($bid > 0 && $ask > 0) ? (($bid + $ask) / 2) : 0);

        if ($mid <= 0) {
            return null;
        }

        $high = (float) ($json['highPrice'] ?? $mid);
        $low = (float) ($json['lowPrice'] ?? $mid);
        $closeMs = (int) ($json['closeTime'] ?? 0);
        $quotedAt = $closeMs > 0
            ? \Carbon\CarbonImmutable::createFromTimestampMs($closeMs, 'UTC')->setTimezone(agricart_display_timezone())->toIso8601String()
            : now(agricart_display_timezone())->toIso8601String();

        return [
            'mid' => $mid,
            'high' => $high > 0 ? $high : $mid,
            'low' => $low > 0 ? $low : $mid,
            'quoted_at' => $quotedAt,
        ];
    }

    /**
     * @param  mixed  $series
     */
    private function extremeFromSeries(mixed $series, bool $max): ?float
    {
        if (! is_array($series) || $series === []) {
            return null;
        }

        $best = null;

        foreach ($series as $value) {
            if (! is_numeric($value)) {
                continue;
            }

            $n = (float) $value;

            if ($n <= 0) {
                continue;
            }

            if ($best === null) {
                $best = $n;

                continue;
            }

            $best = $max ? max($best, $n) : min($best, $n);
        }

        return $best;
    }

    private function halfSpread(string $key, float $price): float
    {
        return match ($key) {
            'xauusd' => 0.15,
            'xagusd' => 0.025,
            'usoil' => 0.03,
            'eurusd', 'usdchf', 'audusd', 'nzdusd', 'usdcad', 'eurchf', 'euraud' => 0.00008,
            'gbpusd', 'eurgbp' => 0.0001,
            'usdjpy', 'gbpjpy', 'audjpy' => 0.008,
            'btcusdt' => 2.5,
            'ethusdt' => 0.35,
            'us30' => 1.2,
            'nas100' => 1.0,
            default => max($price * 0.00005, 0.00001),
        };
    }
}
