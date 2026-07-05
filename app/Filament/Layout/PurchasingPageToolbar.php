<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Purchasing\OverviewPage;

class PurchasingPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return PurchasingSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Purchasing';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'purchasing';
    }
}
