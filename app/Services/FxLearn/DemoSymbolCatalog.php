<?php

namespace App\Services\FxLearn;

class DemoSymbolCatalog
{
    /**
     * Tradable demo symbols.
     *
     * Feeds (official only — no page scraping):
     * - binance: Binance public REST + browser WebSocket (fastest free live)
     * - finnhub: Finnhub REST quote when FINNHUB_API_KEY / DB credential exists
     *
     * @return array<string, array{
     *   label: string,
     *   category: string,
     *   digits: int,
     *   contract: float,
     *   feed: string,
     *   binance?: string,
     *   finnhub?: string,
     *   tv: string,
     *   seed: float
     * }>
     */
    public static function all(): array
    {
        return [
            // Forex — Binance *USDT crosses when available (official live stream); Finnhub for true FX when keyed
            'eurusd' => ['label' => 'EURUSD', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'binance', 'binance' => 'EURUSDT', 'finnhub' => 'OANDA:EUR_USD', 'tv' => 'FX:EURUSD', 'seed' => 1.0842],
            'gbpusd' => ['label' => 'GBPUSD', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'binance', 'binance' => 'GBPUSDT', 'finnhub' => 'OANDA:GBP_USD', 'tv' => 'FX:GBPUSD', 'seed' => 1.2650],
            'audusd' => ['label' => 'AUDUSD', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'binance', 'binance' => 'AUDUSDT', 'finnhub' => 'OANDA:AUD_USD', 'tv' => 'FX:AUDUSD', 'seed' => 0.6520],
            'usdjpy' => ['label' => 'USDJPY', 'category' => 'forex', 'digits' => 3, 'contract' => 1000, 'feed' => 'finnhub', 'finnhub' => 'OANDA:USD_JPY', 'tv' => 'FX:USDJPY', 'seed' => 149.50],
            'usdchf' => ['label' => 'USDCHF', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'finnhub', 'finnhub' => 'OANDA:USD_CHF', 'tv' => 'FX:USDCHF', 'seed' => 0.8800],
            'nzdusd' => ['label' => 'NZDUSD', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'finnhub', 'finnhub' => 'OANDA:NZD_USD', 'tv' => 'FX:NZDUSD', 'seed' => 0.5980],
            'usdcad' => ['label' => 'USDCAD', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'finnhub', 'finnhub' => 'OANDA:USD_CAD', 'tv' => 'FX:USDCAD', 'seed' => 1.3650],
            'eurgbp' => ['label' => 'EURGBP', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'finnhub', 'finnhub' => 'OANDA:EUR_GBP', 'tv' => 'FX:EURGBP', 'seed' => 0.8570],
            'euraud' => ['label' => 'EURAUD', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'finnhub', 'finnhub' => 'OANDA:EUR_AUD', 'tv' => 'FX:EURAUD', 'seed' => 1.6650],
            'eurchf' => ['label' => 'EURCHF', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'finnhub', 'finnhub' => 'OANDA:EUR_CHF', 'tv' => 'FX:EURCHF', 'seed' => 0.9550],
            'gbpjpy' => ['label' => 'GBPJPY', 'category' => 'forex', 'digits' => 3, 'contract' => 1000, 'feed' => 'finnhub', 'finnhub' => 'OANDA:GBP_JPY', 'tv' => 'FX:GBPJPY', 'seed' => 189.20],
            'audjpy' => ['label' => 'AUDJPY', 'category' => 'forex', 'digits' => 3, 'contract' => 1000, 'feed' => 'finnhub', 'finnhub' => 'OANDA:AUD_JPY', 'tv' => 'FX:AUDJPY', 'seed' => 97.50],

            // Commodities — Binance gold proxy + Finnhub where keyed
            'xauusd' => ['label' => 'XAUUSD', 'category' => 'commodities', 'digits' => 2, 'contract' => 100, 'feed' => 'binance', 'binance' => 'PAXGUSDT', 'finnhub' => 'OANDA:XAU_USD', 'tv' => 'OANDA:XAUUSD', 'seed' => 2650.40],
            // Free Finnhub quote uses ETF proxies (SLV/USO) — TV chart stays metals/oil symbols
            'xagusd' => ['label' => 'XAGUSD', 'category' => 'commodities', 'digits' => 3, 'contract' => 100, 'feed' => 'finnhub', 'finnhub' => 'SLV', 'tv' => 'TVC:SILVER', 'seed' => 28.50],
            'usoil' => ['label' => 'USOIL', 'category' => 'commodities', 'digits' => 2, 'contract' => 1, 'feed' => 'finnhub', 'finnhub' => 'USO', 'tv' => 'FX:USOIL', 'seed' => 168.00],

            // Indices — Finnhub free ETF proxies (DIA/QQQ); chart still DJI/NDX
            'us30' => ['label' => 'US30', 'category' => 'indices', 'digits' => 2, 'contract' => 1, 'feed' => 'finnhub', 'finnhub' => 'DIA', 'tv' => 'DJ:DJI', 'seed' => 522.0],
            'nas100' => ['label' => 'NAS100', 'category' => 'indices', 'digits' => 2, 'contract' => 1, 'feed' => 'finnhub', 'finnhub' => 'QQQ', 'tv' => 'NASDAQ:NDX', 'seed' => 520.0],

            // Crypto — Binance official WebSocket (true free live)
            'btcusdt' => ['label' => 'BTCUSDT', 'category' => 'crypto', 'digits' => 2, 'contract' => 1, 'feed' => 'binance', 'binance' => 'BTCUSDT', 'tv' => 'BINANCE:BTCUSDT', 'seed' => 64250.0],
            'ethusdt' => ['label' => 'ETHUSDT', 'category' => 'crypto', 'digits' => 2, 'contract' => 1, 'feed' => 'binance', 'binance' => 'ETHUSDT', 'tv' => 'BINANCE:ETHUSDT', 'seed' => 3200.0],
            'bnbusdt' => ['label' => 'BNBUSDT', 'category' => 'crypto', 'digits' => 2, 'contract' => 1, 'feed' => 'binance', 'binance' => 'BNBUSDT', 'tv' => 'BINANCE:BNBUSDT', 'seed' => 580.0],
            'solusdt' => ['label' => 'SOLUSDT', 'category' => 'crypto', 'digits' => 2, 'contract' => 1, 'feed' => 'binance', 'binance' => 'SOLUSDT', 'tv' => 'BINANCE:SOLUSDT', 'seed' => 145.0],
            'xrpusdt' => ['label' => 'XRPUSDT', 'category' => 'crypto', 'digits' => 4, 'contract' => 1, 'feed' => 'binance', 'binance' => 'XRPUSDT', 'tv' => 'BINANCE:XRPUSDT', 'seed' => 0.62],
            'adausdt' => ['label' => 'ADAUSDT', 'category' => 'crypto', 'digits' => 4, 'contract' => 1, 'feed' => 'binance', 'binance' => 'ADAUSDT', 'tv' => 'BINANCE:ADAUSDT', 'seed' => 0.45],
            'dogeusdt' => ['label' => 'DOGEUSDT', 'category' => 'crypto', 'digits' => 5, 'contract' => 1, 'feed' => 'binance', 'binance' => 'DOGEUSDT', 'tv' => 'BINANCE:DOGEUSDT', 'seed' => 0.12],
            'linkusdt' => ['label' => 'LINKUSDT', 'category' => 'crypto', 'digits' => 3, 'contract' => 1, 'feed' => 'binance', 'binance' => 'LINKUSDT', 'tv' => 'BINANCE:LINKUSDT', 'seed' => 14.5],
            'avaxusdt' => ['label' => 'AVAXUSDT', 'category' => 'crypto', 'digits' => 3, 'contract' => 1, 'feed' => 'binance', 'binance' => 'AVAXUSDT', 'tv' => 'BINANCE:AVAXUSDT', 'seed' => 35.0],
            'dotusdt' => ['label' => 'DOTUSDT', 'category' => 'crypto', 'digits' => 3, 'contract' => 1, 'feed' => 'binance', 'binance' => 'DOTUSDT', 'tv' => 'BINANCE:DOTUSDT', 'seed' => 7.2],
        ];
    }

    /**
     * @return list<string>
     */
    public static function defaultWatchlist(): array
    {
        return ['eurusd', 'xauusd', 'gbpusd', 'us30'];
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public static function categories(): array
    {
        return [
            ['id' => 'forex', 'label' => 'Forex'],
            ['id' => 'commodities', 'label' => 'Commodities'],
            ['id' => 'indices', 'label' => 'Indices'],
            ['id' => 'crypto', 'label' => 'Crypto'],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $symbol): ?array
    {
        return self::all()[$symbol] ?? null;
    }

    /**
     * @return list<array{id: string, label: string, category: string, tv: string, binance: ?string}>
     */
    public static function optionsForCategory(string $category): array
    {
        $out = [];

        foreach (self::all() as $id => $meta) {
            if ($meta['category'] !== $category) {
                continue;
            }

            $out[] = [
                'id' => $id,
                'label' => $meta['label'],
                'category' => $meta['category'],
                'tv' => $meta['tv'],
                'binance' => isset($meta['binance']) ? (string) $meta['binance'] : null,
            ];
        }

        return $out;
    }
}
