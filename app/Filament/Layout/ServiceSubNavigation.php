<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\ServiceCluster;

class ServiceSubNavigation extends ClusterSubNavigation
{
    protected static function clusterClass(): string
    {
        return ServiceCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/service';
    }
}
