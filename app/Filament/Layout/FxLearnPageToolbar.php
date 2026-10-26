<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\FxLearn\TradeTerminalPage;

class FxLearnPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return FxLearnSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return TradeTerminalPage::class;
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

        if ($slug === 'economic-radar') {
            return [
                ...$config,
                'addLabel' => null,
                'hideSearch' => false,
                'hideSearchInput' => true,
                'hideFilters' => true,
                'hideColumns' => true,
                'fxLearnVisitLinks' => [
                    [
                        'key' => 'fc',
                        'label' => __('agricart.pages.fx-learn.econ_visit_fc'),
                        'url' => 'https://www.financecalendar.com/',
                    ],
                    [
                        'key' => 'smt',
                        'label' => __('agricart.pages.fx-learn.econ_visit_smt'),
                        'url' => 'https://showmytrades.com/tools/economic-calendar',
                    ],
                ],
            ];
        }

        return [
            ...$config,
            'addLabel' => null,
            'hideSearch' => true,
            'hideFilters' => true,
            'hideColumns' => true,
        ];
    }
}
