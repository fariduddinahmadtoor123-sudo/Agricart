<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\LogisticsCluster;

class LogisticsSubNavigation extends ClusterSubNavigation
{
    public static function clusterClass(): string
    {
        return LogisticsCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/logistics';
    }
}
