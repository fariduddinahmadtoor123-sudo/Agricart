<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Settings\OverviewPage;

class SettingsPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return SettingsSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Settings';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'settings';
    }
}
