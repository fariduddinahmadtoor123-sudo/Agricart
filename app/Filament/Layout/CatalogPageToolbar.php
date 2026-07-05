<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Catalog\OverviewPage;

class CatalogPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return CatalogSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Catalog';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'catalog';
    }
}
