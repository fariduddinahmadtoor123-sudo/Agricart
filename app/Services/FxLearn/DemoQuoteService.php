<?php

namespace App\Services\FxLearn;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Official free quote sources only (no HTML scraping):
 * - Binance public REST + browser WebSocket (fastest free live)
 * - Finnhub quote REST when an API key is configured (true FX / metals / indices)
 */
class DemoQuoteService
{
    private const CACHE_KEY = 'fxlearn.demo.quotes.v4';

    private const CACHE_TTL = 5;

    public function __construct(
        private readonly FinnhubCredentialService $finnhubCredentials,
    ) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function snapshot(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            return $this->fetchFresh();
        });
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function refresh(): array
    {
        Cache::forget(self::CACHE_KEY);

        return $this->snapshot();
    }

    /**
     * Bypass cache for a single symbol (accurate demo fills).
     *
     * @return array<string, mixed>|null
     */
    public function freshFor(string $symbol): ?array
    {
        $meta = DemoSymbolCatalog::get($symbol);

        if ($meta === null) {
            return null;
        }

        $tick = $this->fetchOneTick($symbol, $meta);

        return $this->hydrate($symbol, $meta, $tick);
    }

    /**
     * Binance stream symbol for browser WebSocket (null = Finnhub/seed only).
     */
    public function streamBinanceSymbol(string $symbol): ?string
    {
        $meta = DemoSymbolCatalog::get($symbol);

        if ($meta === null || ! filled($meta['binance'] ?? null)) {
            return null;
        }

        return (string) $meta['binance'];
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
            $out[$key] = $this->hydrate($key, $meta, $ticks[$key] ?? null);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array{mid: float, bid?: float, ask?: float, high?: float, low?: float, quoted_at?: string, source?: string}|null  $tick
     * @return array<string, mixed>
     */
    private function hydrate(string $key, array $meta, ?array $tick): array
    {
        $live = is_array($tick);
        $price = $live ? (float) $tick['mid'] : (float) $meta['seed'];
        $digits = (int) $meta['digits'];
        $half = $this->halfSpread($key, $price);

        $bid = $live && isset($tick['bid']) ? (float) $tick['bid'] : ($price - $half);
        $ask = $live && isset($tick['ask']) ? (float) $tick['ask'] : ($price + $half);
        $high = $live && isset($tick['high']) ? (float) $tick['high'] : round($price * 1.002, $digits);
        $low = $live && isset($tick['low']) ? (float) $tick['low'] : round($price * 0.998, $digits);

        if ($high < $low) {
            [$high, $low] = [$low, $high];
        }

        $source = $live ? (string) ($tick['source'] ?? 'live') : 'seed';
        $streamBinance = $this->streamBinanceSymbol($key);

        return [
            'label' => $meta['label'],
            'category' => $meta['category'],
            'digits' => $digits,
            'contract' => (float) $meta['contract'],
            'tv' => (string) $meta['tv'],
            'mid' => round(($bid + $ask) / 2, $digits),
            'bid' => round($bid, $digits),
            'ask' => round($ask, $digits),
            'high' => round($high, $digits),
            'low' => round($low, $digits),
            'live' => $live,
            'quoted_at' => $live ? ($tick['quoted_at'] ?? now()->toIso8601String()) : null,
            'feed' => (string) ($meta['feed'] ?? ''),
            'source' => $source,
            'binance' => $streamBinance,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $catalog
     * @return array<string, array{mid: float, bid?: float, ask?: float, high?: float, low?: float, quoted_at?: string, source?: string}>
     */
    private function fetchAllTicks(array $catalog): array
    {
        $ticks = [];
        $finnhubToken = $this->finnhubCredentials->resolveToken();

        try {
            $responses = Http::pool(function ($pool) use ($catalog, $finnhubToken) {
                $requests = [];

                foreach ($catalog as $key => $meta) {
                    $route = $this->routeFor($meta, $finnhubToken);

                    if ($route === 'binance') {
                        $requests[$key] = $pool->as($key)
                            ->timeout(8)
                            ->withHeaders(['User-Agent' => 'Agricart-FXLearn/1.0'])
                            ->get('https://api.binance.com/api/v3/ticker/24hr', [
                                'symbol' => (string) $meta['binance'],
                            ]);
                    } elseif ($route === 'finnhub') {
                        $requests[$key] = $pool->as($key)
                            ->timeout(8)
                            ->withHeaders(['User-Agent' => 'Agricart-FXLearn/1.0'])
                            ->get('https://finnhub.io/api/v1/quote', [
                                'symbol' => (string) $meta['finnhub'],
                                'token' => $finnhubToken,
                            ]);
                    }
                }

                return $requests;
            });

            foreach ($catalog as $key => $meta) {
                $route = $this->routeFor($meta, $finnhubToken);
                $response = $responses[$key] ?? null;

                if (! is_object($response) || ! method_exists($response, 'successful') || ! $response->successful()) {
                    if ($route === 'binance' && $finnhubToken !== null && filled($meta['finnhub'] ?? null)) {
                        $fallback = $this->fetchFinnhubTick((string) $meta['finnhub'], $finnhubToken);

                        if ($fallback !== null) {
                            $ticks[$key] = $fallback;
                        }
                    }

                    continue;
                }

                if ($route === 'binance') {
                    $parsed = $this->parseBinancePayload($response->json());

                    if ($parsed !== null) {
                        $ticks[$key] = $parsed;
                    }

                    continue;
                }

                $parsed = $this->parseFinnhubPayload($response->json());

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
     * Prefer Binance whenever a public pair exists (free + WebSocket-fast).
     * Finnhub free tier blocks OANDA FX; it still helps for equity/ETF proxies.
     *
     * @param  array<string, mixed>  $meta
     */
    private function routeFor(array $meta, ?string $finnhubToken): ?string
    {
        if (filled($meta['binance'] ?? null)) {
            return 'binance';
        }

        if ($finnhubToken !== null && filled($meta['finnhub'] ?? null)) {
            return 'finnhub';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array{mid: float, bid?: float, ask?: float, high?: float, low?: float, quoted_at?: string, source?: string}|null
     */
    private function fetchOneTick(string $symbol, array $meta): ?array
    {
        try {
            $token = $this->finnhubCredentials->resolveToken();
            $route = $this->routeFor($meta, $token);

            if ($route === 'binance') {
                $response = Http::timeout(8)
                    ->withHeaders(['User-Agent' => 'Agricart-FXLearn/1.0'])
                    ->get('https://api.binance.com/api/v3/ticker/24hr', [
                        'symbol' => (string) $meta['binance'],
                    ]);

                if ($response->successful()) {
                    $parsed = $this->parseBinancePayload($response->json());

                    if ($parsed !== null) {
                        return $parsed;
                    }
                }

                if ($token !== null && filled($meta['finnhub'] ?? null)) {
                    return $this->fetchFinnhubTick((string) $meta['finnhub'], $token);
                }
            }

            if ($route === 'finnhub' && $token !== null) {
                return $this->fetchFinnhubTick((string) $meta['finnhub'], $token);
            }
        } catch (Throwable $e) {
            Log::warning('FX Learn single quote failed', ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * @return array{mid: float, bid: float, ask: float, high: float, low: float, quoted_at: string, source: string}|null
     */
    private function fetchFinnhubTick(string $finnhubSymbol, string $token): ?array
    {
        $response = Http::timeout(8)
            ->withHeaders(['User-Agent' => 'Agricart-FXLearn/1.0'])
            ->get('https://finnhub.io/api/v1/quote', [
                'symbol' => $finnhubSymbol,
                'token' => $token,
            ]);

        if (! $response->successful()) {
            return null;
        }

        return $this->parseFinnhubPayload($response->json());
    }

    /**
     * @param  mixed  $json
     * @return array{mid: float, bid: float, ask: float, high: float, low: float, quoted_at: string, source: string}|null
     */
    private function parseBinancePayload(mixed $json): ?array
    {
        if (! is_array($json)) {
            return null;
        }

        $bid = (float) ($json['bidPrice'] ?? 0);
        $ask = (float) ($json['askPrice'] ?? 0);
        $last = (float) ($json['lastPrice'] ?? 0);
        $mid = ($bid > 0 && $ask > 0) ? (($bid + $ask) / 2) : $last;

        if ($mid <= 0) {
            return null;
        }

        if ($bid <= 0 || $ask <= 0) {
            $half = max($mid * 0.00005, 0.00001);
            $bid = $mid - $half;
            $ask = $mid + $half;
        }

        $high = (float) ($json['highPrice'] ?? $mid);
        $low = (float) ($json['lowPrice'] ?? $mid);
        $closeMs = (int) ($json['closeTime'] ?? 0);
        $quotedAt = $closeMs > 0
            ? CarbonImmutable::createFromTimestampMs($closeMs, 'UTC')->setTimezone(agricart_display_timezone())->toIso8601String()
            : now(agricart_display_timezone())->toIso8601String();

        return [
            'mid' => $mid,
            'bid' => $bid,
            'ask' => $ask,
            'high' => $high > 0 ? $high : $mid,
            'low' => $low > 0 ? $low : $mid,
            'quoted_at' => $quotedAt,
            'source' => 'binance',
        ];
    }

    /**
     * @param  mixed  $json
     * @return array{mid: float, bid: float, ask: float, high: float, low: float, quoted_at: string, source: string}|null
     */
    private function parseFinnhubPayload(mixed $json): ?array
    {
        if (! is_array($json)) {
            return null;
        }

        $current = (float) ($json['c'] ?? 0);

        if ($current <= 0) {
            return null;
        }

        $high = (float) ($json['h'] ?? $current);
        $low = (float) ($json['l'] ?? $current);
        $half = max($current * 0.00005, 0.00001);
        $ts = (int) ($json['t'] ?? 0);
        $quotedAt = $ts > 0
            ? CarbonImmutable::createFromTimestamp($ts, 'UTC')->setTimezone(agricart_display_timezone())->toIso8601String()
            : now(agricart_display_timezone())->toIso8601String();

        return [
            'mid' => $current,
            'bid' => $current - $half,
            'ask' => $current + $half,
            'high' => $high > 0 ? $high : $current,
            'low' => $low > 0 ? $low : $current,
            'quoted_at' => $quotedAt,
            'source' => 'finnhub',
        ];
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
            'btcusdt', 'ethusdt', 'bnbusdt', 'solusdt', 'avaxusdt', 'linkusdt', 'dotusdt' => max($price * 0.00005, 0.01),
            'xrpusdt', 'adausdt', 'dogeusdt' => max($price * 0.00008, 0.00001),
            'us30', 'nas100' => 0.05,
            default => max($price * 0.00005, 0.00001),
        };
    }
}
