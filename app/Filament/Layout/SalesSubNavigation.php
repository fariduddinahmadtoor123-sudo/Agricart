<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\SalesCluster;

class SalesSubNavigation extends ClusterSubNavigation
{
    protected static function clusterClass(): string
    {
        return SalesCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/sales';
    }
}
