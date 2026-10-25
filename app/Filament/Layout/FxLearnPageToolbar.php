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

        return [
            ...$config,
            'addLabel' => null,
            'hideSearch' => true,
            'hideFilters' => true,
            'hideColumns' => true,
        ];
    }
}
