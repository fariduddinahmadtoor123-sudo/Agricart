<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Sales\OverviewPage;

class SalesPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return SalesSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Sales';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'sales';
    }
}
