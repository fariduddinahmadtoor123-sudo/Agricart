<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Service\OverviewPage;

class ServicePageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return ServiceSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Service';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'service';
    }
}
