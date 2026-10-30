<?php

namespace App\Services\FxLearn;

class DemoSymbolCatalog
{
    /**
     * Tradable demo symbols with quote feed + TradingView chart id.
     *
     * @return array<string, array{
     *   label: string,
     *   category: string,
     *   digits: int,
     *   contract: float,
     *   feed: string,
     *   yahoo?: string,
     *   binance?: string,
     *   tv: string,
     *   seed: float
     * }>
     */
    public static function all(): array
    {
        return [
            'eurusd' => ['label' => 'EURUSD', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'yahoo', 'yahoo' => 'EURUSD=X', 'tv' => 'FX:EURUSD', 'seed' => 1.0842],
            'gbpusd' => ['label' => 'GBPUSD', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'yahoo', 'yahoo' => 'GBPUSD=X', 'tv' => 'FX:GBPUSD', 'seed' => 1.2650],
            'usdjpy' => ['label' => 'USDJPY', 'category' => 'forex', 'digits' => 3, 'contract' => 1000, 'feed' => 'yahoo', 'yahoo' => 'USDJPY=X', 'tv' => 'FX:USDJPY', 'seed' => 149.50],
            'usdchf' => ['label' => 'USDCHF', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'yahoo', 'yahoo' => 'USDCHF=X', 'tv' => 'FX:USDCHF', 'seed' => 0.8800],
            'audusd' => ['label' => 'AUDUSD', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'yahoo', 'yahoo' => 'AUDUSD=X', 'tv' => 'FX:AUDUSD', 'seed' => 0.6520],
            'nzdusd' => ['label' => 'NZDUSD', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'yahoo', 'yahoo' => 'NZDUSD=X', 'tv' => 'FX:NZDUSD', 'seed' => 0.5980],
            'usdcad' => ['label' => 'USDCAD', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'yahoo', 'yahoo' => 'USDCAD=X', 'tv' => 'FX:USDCAD', 'seed' => 1.3650],
            'eurgbp' => ['label' => 'EURGBP', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'yahoo', 'yahoo' => 'EURGBP=X', 'tv' => 'FX:EURGBP', 'seed' => 0.8570],
            'euraud' => ['label' => 'EURAUD', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'yahoo', 'yahoo' => 'EURAUD=X', 'tv' => 'FX:EURAUD', 'seed' => 1.6650],
            'eurchf' => ['label' => 'EURCHF', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'yahoo', 'yahoo' => 'EURCHF=X', 'tv' => 'FX:EURCHF', 'seed' => 0.9550],
            'gbpjpy' => ['label' => 'GBPJPY', 'category' => 'forex', 'digits' => 3, 'contract' => 1000, 'feed' => 'yahoo', 'yahoo' => 'GBPJPY=X', 'tv' => 'FX:GBPJPY', 'seed' => 189.20],
            'audjpy' => ['label' => 'AUDJPY', 'category' => 'forex', 'digits' => 3, 'contract' => 1000, 'feed' => 'yahoo', 'yahoo' => 'AUDJPY=X', 'tv' => 'FX:AUDJPY', 'seed' => 97.50],
            'xauusd' => ['label' => 'XAUUSD', 'category' => 'commodities', 'digits' => 2, 'contract' => 100, 'feed' => 'yahoo', 'yahoo' => 'GC=F', 'tv' => 'OANDA:XAUUSD', 'seed' => 2650.40],
            'xagusd' => ['label' => 'XAGUSD', 'category' => 'commodities', 'digits' => 3, 'contract' => 5000, 'feed' => 'yahoo', 'yahoo' => 'SI=F', 'tv' => 'TVC:SILVER', 'seed' => 31.20],
            'usoil' => ['label' => 'USOIL', 'category' => 'commodities', 'digits' => 2, 'contract' => 100, 'feed' => 'yahoo', 'yahoo' => 'CL=F', 'tv' => 'FX:USOIL', 'seed' => 72.50],
            'us30' => ['label' => 'US30', 'category' => 'indices', 'digits' => 1, 'contract' => 1, 'feed' => 'yahoo', 'yahoo' => 'YM=F', 'tv' => 'DJ:DJI', 'seed' => 39840.0],
            'nas100' => ['label' => 'NAS100', 'category' => 'indices', 'digits' => 1, 'contract' => 1, 'feed' => 'yahoo', 'yahoo' => 'NQ=F', 'tv' => 'NASDAQ:NDX', 'seed' => 17850.0],
            'btcusdt' => ['label' => 'BTCUSDT', 'category' => 'crypto', 'digits' => 2, 'contract' => 1, 'feed' => 'binance', 'binance' => 'BTCUSDT', 'tv' => 'BINANCE:BTCUSDT', 'seed' => 64250.0],
            'ethusdt' => ['label' => 'ETHUSDT', 'category' => 'crypto', 'digits' => 2, 'contract' => 1, 'feed' => 'binance', 'binance' => 'ETHUSDT', 'tv' => 'BINANCE:ETHUSDT', 'seed' => 3200.0],
        ];
    }

    /**
     * Default front-line symbols for a new demo desk.
     *
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
     * @return list<array{id: string, label: string, category: string, tv: string}>
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
            ];
        }

        return $out;
    }
}
