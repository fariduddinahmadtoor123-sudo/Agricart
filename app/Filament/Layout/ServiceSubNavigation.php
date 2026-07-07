<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\ServiceCluster;

class ServiceSubNavigation extends ClusterSubNavigation
{
    public static function clusterClass(): string
    {
        return ServiceCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/service';
    }
}
