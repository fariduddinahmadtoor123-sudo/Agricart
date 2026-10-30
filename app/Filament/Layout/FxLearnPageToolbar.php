<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\FxLearn\EconomicRadarPage;

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

        if ($slug !== 'economic-radar') {
            return [
                ...$config,
                'addLabel' => null,
                'hideSearch' => true,
                'hideFilters' => true,
                'hideColumns' => true,
            ];
        }

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
            'fxLearnSettings' => [
                'label' => __('agricart.pages.fx-learn.settings'),
                'event' => 'fx-learn-radar-settings',
            ],
        ];
    }
}
