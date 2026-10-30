<?php

namespace App\Services\FxLearn;

/**
 * TradingView symbols proven to fill Technical Analysis gauges (from FTI scanner notes).
 */
class MarketSignalCatalog
{
    /**
     * @return list<array{id: string, label: string}>
     */
    public static function categories(): array
    {
        return [
            ['id' => 'forex', 'label' => 'forex'],
            ['id' => 'commodities', 'label' => 'commodities'],
            ['id' => 'indices', 'label' => 'indices'],
            ['id' => 'crypto', 'label' => 'crypto'],
        ];
    }

    /**
     * @return array<string, list<array{id: string, label: string, symbol: string}>>
     */
    public static function assetsByCategory(): array
    {
        return [
            'forex' => [
                ['id' => 'eurusd', 'label' => 'EURUSD', 'symbol' => 'FX:EURUSD'],
                ['id' => 'gbpusd', 'label' => 'GBPUSD', 'symbol' => 'FX:GBPUSD'],
                ['id' => 'usdjpy', 'label' => 'USDJPY', 'symbol' => 'FX:USDJPY'],
                ['id' => 'usdchf', 'label' => 'USDCHF', 'symbol' => 'FX:USDCHF'],
                ['id' => 'audusd', 'label' => 'AUDUSD', 'symbol' => 'FX:AUDUSD'],
                ['id' => 'nzdusd', 'label' => 'NZDUSD', 'symbol' => 'FX:NZDUSD'],
                ['id' => 'usdcad', 'label' => 'USDCAD', 'symbol' => 'FX:USDCAD'],
            ],
            'commodities' => [
                ['id' => 'xauusd', 'label' => 'XAUUSD', 'symbol' => 'OANDA:XAUUSD'],
                ['id' => 'xagusd', 'label' => 'XAGUSD', 'symbol' => 'TVC:SILVER'],
                ['id' => 'usoil', 'label' => 'USOIL', 'symbol' => 'FX:USOIL'],
            ],
            'indices' => [
                ['id' => 'us30', 'label' => 'US30', 'symbol' => 'DJ:DJI'],
                ['id' => 'nas100', 'label' => 'NAS100', 'symbol' => 'NASDAQ:NDX'],
            ],
            'crypto' => [
                ['id' => 'btcusdt', 'label' => 'BTCUSDT', 'symbol' => 'BINANCE:BTCUSDT'],
                ['id' => 'ethusdt', 'label' => 'ETHUSDT', 'symbol' => 'BINANCE:ETHUSDT'],
            ],
        ];
    }

    /**
     * @return list<array{id: string, label: string, interval: string}>
     */
    public static function intervals(): array
    {
        // Full set accepted by TradingView Technical Analysis widget.
        return [
            ['id' => '1m', 'label' => '1m', 'interval' => '1m'],
            ['id' => '5m', 'label' => '5m', 'interval' => '5m'],
            ['id' => '15m', 'label' => '15m', 'interval' => '15m'],
            ['id' => '30m', 'label' => '30m', 'interval' => '30m'],
            ['id' => '1h', 'label' => '1H', 'interval' => '1h'],
            ['id' => '2h', 'label' => '2H', 'interval' => '2h'],
            ['id' => '4h', 'label' => '4H', 'interval' => '4h'],
            ['id' => '1D', 'label' => '1D', 'interval' => '1D'],
            ['id' => '1W', 'label' => '1W', 'interval' => '1W'],
            ['id' => '1M', 'label' => '1M', 'interval' => '1M'],
        ];
    }

    /**
     * @return list<array{id: string, label: string, symbol: string}>
     */
    public static function assetsFor(string $category): array
    {
        $map = self::assetsByCategory();

        return $map[$category] ?? $map['forex'];
    }

    /**
     * @return array{id: string, label: string, symbol: string}|null
     */
    public static function findAsset(string $category, string $assetId): ?array
    {
        foreach (self::assetsFor($category) as $asset) {
            if ($asset['id'] === $assetId) {
                return $asset;
            }
        }

        return self::assetsFor($category)[0] ?? null;
    }

    /**
     * Flat list for ticker tape.
     *
     * @return list<array{proName: string, title: string}>
     */
    public static function tapeSymbols(?string $category = null): array
    {
        $groups = $category !== null && $category !== 'all'
            ? [$category => self::assetsFor($category)]
            : self::assetsByCategory();

        $out = [];

        foreach ($groups as $assets) {
            foreach ($assets as $asset) {
                $out[] = [
                    'proName' => $asset['symbol'],
                    'title' => $asset['label'],
                ];
            }
        }

        return $out;
    }

    /**
     * @return array{id: string, label: string, interval: string}
     */
    public static function findInterval(string $id): array
    {
        foreach (self::intervals() as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }

        return self::intervals()[4]; // 1H
    }
}
