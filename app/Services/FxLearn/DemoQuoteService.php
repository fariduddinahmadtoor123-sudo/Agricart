<?php

namespace App\Services\FxLearn;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DemoQuoteService
{
    /**
     * @return array<string, array{bid: float, ask: float, mid: float, live: bool, digits: int, label: string, category: string, contract: float}>
     */
    public function snapshot(): array
    {
        return Cache::remember('fxlearn.demo.quotes.v1', 20, function (): array {
            return $this->fetchFresh();
        });
    }

    public function refresh(): array
    {
        Cache::forget('fxlearn.demo.quotes.v1');

        return $this->snapshot();
    }

    /**
     * @return array<string, array{bid: float, ask: float, mid: float, live: bool, digits: int, label: string, category: string, contract: float}>
     */
    private function fetchFresh(): array
    {
        $catalog = DemoSymbolCatalog::all();
        $prices = $this->fetchAllPrices($catalog);
        $out = [];

        foreach ($catalog as $key => $meta) {
            $fetched = $prices[$key] ?? null;
            $live = $fetched !== null;
            $price = $fetched ?? (float) $meta['seed'];
            $half = $this->halfSpread($key, $price);
            $digits = (int) $meta['digits'];

            $out[$key] = [
                'label' => $meta['label'],
                'category' => $meta['category'],
                'digits' => $digits,
                'contract' => (float) $meta['contract'],
                'mid' => round($price, $digits),
                'bid' => round($price - $half, $digits),
                'ask' => round($price + $half, $digits),
                'live' => $live,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, array<string, mixed>>  $catalog
     * @return array<string, float>
     */
    private function fetchAllPrices(array $catalog): array
    {
        $prices = [];

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
                            ->get('https://api.binance.com/api/v3/ticker/bookTicker', [
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
                    $bid = (float) $response->json('bidPrice');
                    $ask = (float) $response->json('askPrice');

                    if ($bid > 0 && $ask > 0) {
                        $prices[$key] = ($bid + $ask) / 2;
                    }

                    continue;
                }

                $parsed = $this->parseYahooPayload($response->json());

                if ($parsed !== null) {
                    $prices[$key] = $parsed;
                }
            }
        } catch (Throwable $e) {
            Log::warning('FX Learn quote pool failed', ['error' => $e->getMessage()]);
        }

        return $prices;
    }

    /**
     * @param  mixed  $json
     */
    private function parseYahooPayload(mixed $json): ?float
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

        return $price > 0 ? $price : null;
    }

    private function halfSpread(string $key, float $price): float
    {
        return match ($key) {
            'xauusd' => 0.15,
            'xagusd' => 0.025,
            'usoil' => 0.03,
            'eurusd', 'usdchf', 'audusd' => 0.00008,
            'gbpusd' => 0.0001,
            'usdjpy' => 0.008,
            'btcusdt' => 2.5,
            'ethusdt' => 0.35,
            'us30' => 1.2,
            'nas100' => 1.0,
            default => max($price * 0.00005, 0.00001),
        };
    }
}
