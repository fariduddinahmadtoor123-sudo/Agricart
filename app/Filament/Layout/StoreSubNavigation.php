<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\StoreCluster;

class StoreSubNavigation extends ClusterSubNavigation
{
    protected static function clusterClass(): string
    {
        return StoreCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/store';
    }
}
