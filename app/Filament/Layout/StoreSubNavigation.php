<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\StoreCluster;

class StoreSubNavigation extends ClusterSubNavigation
{
    public static function clusterClass(): string
    {
        return StoreCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/store';
    }
}
