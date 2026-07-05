<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Store\OverviewPage;

class StorePageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return StoreSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Store';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'store';
    }
}
