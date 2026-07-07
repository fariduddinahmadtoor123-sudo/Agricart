<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\MarketplaceCluster;

class MarketplaceSubNavigation extends ClusterSubNavigation
{
    public static function clusterClass(): string
    {
        return MarketplaceCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/marketplace';
    }
}
