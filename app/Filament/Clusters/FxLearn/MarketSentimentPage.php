<?php

namespace App\Filament\Clusters\FxLearn;

use App\Filament\Clusters\FxLearnCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class MarketSentimentPage extends BaseModulePage
{
    protected static ?string $cluster = FxLearnCluster::class;

    protected static ?string $navigationLabel = 'Market Sentiment';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'market-sentiment';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected string $view = 'filament.pages.fx-learn.market-sentiment';

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return FxLearnCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function bootData(): array
    {
        return [
            'locale' => 'en',
            'currencies' => ['EUR', 'USD', 'JPY', 'GBP', 'CHF', 'AUD', 'CAD', 'NZD'],
            'pairs' => [
                ['id' => 'eurusd', 'label' => 'EURUSD', 'symbol' => 'FX:EURUSD'],
                ['id' => 'gbpusd', 'label' => 'GBPUSD', 'symbol' => 'FX:GBPUSD'],
                ['id' => 'usdjpy', 'label' => 'USDJPY', 'symbol' => 'FX:USDJPY'],
                ['id' => 'usdchf', 'label' => 'USDCHF', 'symbol' => 'FX:USDCHF'],
                ['id' => 'audusd', 'label' => 'AUDUSD', 'symbol' => 'FX:AUDUSD'],
                ['id' => 'xauusd', 'label' => 'XAUUSD', 'symbol' => 'OANDA:XAUUSD'],
            ],
            'intervals' => [
                ['id' => '15', 'label' => '15m'],
                ['id' => '60', 'label' => '1H'],
                ['id' => '240', 'label' => '4H'],
                ['id' => '1D', 'label' => '1D'],
            ],
            'tapeSymbols' => [
                ['proName' => 'FX_IDC:EURUSD', 'title' => 'EUR/USD'],
                ['proName' => 'FX_IDC:GBPUSD', 'title' => 'GBP/USD'],
                ['proName' => 'FX_IDC:USDJPY', 'title' => 'USD/JPY'],
                ['proName' => 'FX_IDC:USDCHF', 'title' => 'USD/CHF'],
                ['proName' => 'FX_IDC:AUDUSD', 'title' => 'AUD/USD'],
                ['proName' => 'FX_IDC:USDCAD', 'title' => 'USD/CAD'],
                ['proName' => 'OANDA:XAUUSD', 'title' => 'XAU/USD'],
                ['proName' => 'BINANCE:BTCUSDT', 'title' => 'BTC'],
            ],
            'overviewTabs' => [
                [
                    'title' => 'Major',
                    'symbols' => [
                        ['s' => 'FX_IDC:EURUSD', 'd' => 'EUR/USD'],
                        ['s' => 'FX_IDC:USDJPY', 'd' => 'USD/JPY'],
                        ['s' => 'FX_IDC:GBPUSD', 'd' => 'GBP/USD'],
                        ['s' => 'FX_IDC:AUDUSD', 'd' => 'AUD/USD'],
                        ['s' => 'FX_IDC:USDCAD', 'd' => 'USD/CAD'],
                        ['s' => 'FX_IDC:USDCHF', 'd' => 'USD/CHF'],
                    ],
                ],
                [
                    'title' => 'Metals & Oil',
                    'symbols' => [
                        ['s' => 'OANDA:XAUUSD', 'd' => 'Gold'],
                        ['s' => 'TVC:SILVER', 'd' => 'Silver'],
                        ['s' => 'FX:USOIL', 'd' => 'US Oil'],
                    ],
                ],
                [
                    'title' => 'Indices',
                    'symbols' => [
                        ['s' => 'FX:US30', 'd' => 'US30'],
                        ['s' => 'FX:NAS100', 'd' => 'NAS100'],
                        ['s' => 'FOREXCOM:SPXUSD', 'd' => 'S&P 500'],
                    ],
                ],
            ],
            'labels' => [
                'tapeTitle' => __('agricart.pages.fx-learn.sent_tape_title'),
                'tapeHint' => __('agricart.pages.fx-learn.sent_tape_hint'),
                'overviewTitle' => __('agricart.pages.fx-learn.sent_overview_title'),
                'overviewHint' => __('agricart.pages.fx-learn.sent_overview_hint'),
                'heatTitle' => __('agricart.pages.fx-learn.sent_heat_title'),
                'heatHint' => __('agricart.pages.fx-learn.sent_heat_hint'),
                'crossTitle' => __('agricart.pages.fx-learn.sent_cross_title'),
                'crossHint' => __('agricart.pages.fx-learn.sent_cross_hint'),
                'taTitle' => __('agricart.pages.fx-learn.sent_ta_title'),
                'taHint' => __('agricart.pages.fx-learn.sent_ta_hint'),
                'pair' => __('agricart.pages.fx-learn.sent_pair'),
                'tf' => __('agricart.pages.fx-learn.sent_tf'),
                'powered' => __('agricart.pages.fx-learn.sent_powered'),
            ],
        ];
    }
}
