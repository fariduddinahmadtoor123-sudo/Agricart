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

        if ((string) ($config['resourceSlug'] ?? '') !== 'economic-radar') {
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
            'fxLearnQuickFilters' => [
                ['key' => 'all', 'label' => __('agricart.pages.fx-learn.filter_all')],
                ['key' => 'high', 'label' => __('agricart.pages.fx-learn.filter_high')],
                ['key' => 'usd', 'label' => __('agricart.pages.fx-learn.filter_usd')],
                ['key' => 'actual', 'label' => __('agricart.pages.fx-learn.filter_actual')],
                ['key' => 'practice', 'label' => __('agricart.pages.fx-learn.filter_practice')],
            ],
            'fxLearnSettings' => [
                'label' => __('agricart.pages.fx-learn.settings'),
                'event' => 'fx-learn-radar-settings',
            ],
        ];
    }
}
