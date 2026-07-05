<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Marketplace\OverviewPage;

class MarketplacePageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return MarketplaceSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Marketplace';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'marketplace';
    }
}
