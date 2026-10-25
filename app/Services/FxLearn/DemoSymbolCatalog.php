<?php

namespace App\Services\FxLearn;

class DemoSymbolCatalog
{
    /**
     * @return array<string, array{
     *   label: string,
     *   category: string,
     *   digits: int,
     *   contract: float,
     *   feed: string,
     *   yahoo?: string,
     *   binance?: string,
     *   seed: float
     * }>
     */
    public static function all(): array
    {
        return [
            'eurusd' => ['label' => 'EURUSD', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'yahoo', 'yahoo' => 'EURUSD=X', 'seed' => 1.0842],
            'gbpusd' => ['label' => 'GBPUSD', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'yahoo', 'yahoo' => 'GBPUSD=X', 'seed' => 1.2650],
            'usdjpy' => ['label' => 'USDJPY', 'category' => 'forex', 'digits' => 3, 'contract' => 1000, 'feed' => 'yahoo', 'yahoo' => 'USDJPY=X', 'seed' => 149.50],
            'usdchf' => ['label' => 'USDCHF', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'yahoo', 'yahoo' => 'USDCHF=X', 'seed' => 0.8800],
            'audusd' => ['label' => 'AUDUSD', 'category' => 'forex', 'digits' => 5, 'contract' => 100000, 'feed' => 'yahoo', 'yahoo' => 'AUDUSD=X', 'seed' => 0.6520],
            'xauusd' => ['label' => 'XAUUSD', 'category' => 'commodities', 'digits' => 2, 'contract' => 100, 'feed' => 'yahoo', 'yahoo' => 'GC=F', 'seed' => 2650.40],
            'xagusd' => ['label' => 'XAGUSD', 'category' => 'commodities', 'digits' => 3, 'contract' => 5000, 'feed' => 'yahoo', 'yahoo' => 'SI=F', 'seed' => 31.20],
            'usoil' => ['label' => 'USOIL', 'category' => 'commodities', 'digits' => 2, 'contract' => 100, 'feed' => 'yahoo', 'yahoo' => 'CL=F', 'seed' => 72.50],
            'us30' => ['label' => 'US30', 'category' => 'indices', 'digits' => 1, 'contract' => 1, 'feed' => 'yahoo', 'yahoo' => 'YM=F', 'seed' => 39840.0],
            'nas100' => ['label' => 'NAS100', 'category' => 'indices', 'digits' => 1, 'contract' => 1, 'feed' => 'yahoo', 'yahoo' => 'NQ=F', 'seed' => 17850.0],
            'btcusdt' => ['label' => 'BTCUSDT', 'category' => 'crypto', 'digits' => 2, 'contract' => 1, 'feed' => 'binance', 'binance' => 'BTCUSDT', 'seed' => 64250.0],
            'ethusdt' => ['label' => 'ETHUSDT', 'category' => 'crypto', 'digits' => 2, 'contract' => 1, 'feed' => 'binance', 'binance' => 'ETHUSDT', 'seed' => 3200.0],
        ];
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

    public static function get(string $symbol): ?array
    {
        return self::all()[$symbol] ?? null;
    }
}
