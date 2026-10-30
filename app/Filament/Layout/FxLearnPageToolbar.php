<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\FxLearn\EconomicRadarPage;
use App\Filament\Clusters\FxLearn\MarketSignalsPage;
use App\Filament\Clusters\FxLearn\TradeTerminalPage;
use Livewire\Livewire;

class FxLearnPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return FxLearnSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return EconomicRadarPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'FX Learn';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'fx-learn';
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function config(): ?array
    {
        $config = parent::config();

        if ($config === null) {
            return null;
        }

        $slug = (string) ($config['resourceSlug'] ?? '');

        if ($slug === 'market-signals') {
            $signals = Livewire::current();
            $category = $signals instanceof MarketSignalsPage ? $signals->category : 'forex';
            $intervalId = $signals instanceof MarketSignalsPage ? $signals->intervalId : '1h';

            return [
                ...$config,
                'addLabel' => null,
                'hideSearch' => false,
                'hideSearchInput' => false,
                'hideFilters' => true,
                'hideColumns' => true,
                'searchPlaceholder' => __('agricart.pages.fx-learn.sig_search_ph'),
                'livewirePrefix' => 'fx-learn-signals',
                'fxLearnRadarChrome' => true,
                'fxLearnSignalsDropdowns' => true,
                'fxLearnSignalsCategory' => $category,
                'fxLearnSignalsInterval' => $intervalId,
                'fxLearnVisitLinks' => [
                    [
                        'key' => 'tv',
                        'label' => __('agricart.pages.fx-learn.sig_visit_tv'),
                        'url' => 'https://www.tradingview.com/markets/currencies/',
                    ],
                    [
                        'key' => 'heat',
                        'label' => __('agricart.pages.fx-learn.sig_visit_heatmap'),
                        'url' => 'https://www.tradingview.com/heatmap/forex/',
                    ],
                ],
            ];
        }

        if ($slug === 'trade-terminal') {
            $terminal = Livewire::current();
            $category = $terminal instanceof TradeTerminalPage ? $terminal->category : 'forex';

            return [
                ...$config,
                'addLabel' => null,
                'hideSearch' => true,
                'hideSearchInput' => true,
                'hideFilters' => true,
                'hideColumns' => true,
                'fxLearnRadarChrome' => true,
                'fxLearnTerminalChrome' => true,
                'fxLearnTerminalCategories' => true,
                'fxLearnTerminalCategory' => $category,
            ];
        }

        if ($slug !== 'economic-radar') {
            return [
                ...$config,
                'addLabel' => null,
                'hideSearch' => true,
                'hideFilters' => true,
                'hideColumns' => true,
            ];
        }

        $radar = Livewire::current();
        $ccy = $radar instanceof EconomicRadarPage ? $radar->ccyFilter : 'all';
        $impact = $radar instanceof EconomicRadarPage ? $radar->impactFilter : 'all';

        return [
            ...$config,
            'addLabel' => null,
            'hideSearch' => false,
            'hideSearchInput' => false,
            'hideFilters' => true,
            'hideColumns' => true,
            'searchPlaceholder' => __('agricart.pages.fx-learn.radar_search_ph'),
            'livewirePrefix' => 'fx-learn-radar',
            'fxLearnRadarChrome' => true,
            'fxLearnVisitLinks' => [
                [
                    'key' => 'ff',
                    'label' => __('agricart.pages.fx-learn.visit_ff'),
                    'url' => 'https://www.forexfactory.com/calendar',
                ],
                [
                    'key' => 'inv',
                    'label' => __('agricart.pages.fx-learn.visit_inv'),
                    'url' => 'https://www.investing.com/economic-calendar/',
                ],
                [
                    'key' => 'tv',
                    'label' => __('agricart.pages.fx-learn.visit_tv'),
                    'url' => 'https://www.tradingview.com/economic-calendar/',
                ],
            ],
            'fxLearnDropdownFilters' => true,
            'fxLearnRadarCcy' => $ccy,
            'fxLearnRadarImpact' => $impact,
            // Calendar uses free FF+FC feeds — no Finnhub API settings chrome.
        ];
    }
}
