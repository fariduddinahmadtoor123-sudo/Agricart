<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\PurchasingCluster;

class PurchasingSubNavigation extends ClusterSubNavigation
{
    public static function clusterClass(): string
    {
        return PurchasingCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/purchasing';
    }
}
