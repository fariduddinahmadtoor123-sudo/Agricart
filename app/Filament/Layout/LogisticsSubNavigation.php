<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\LogisticsCluster;

class LogisticsSubNavigation extends ClusterSubNavigation
{
    protected static function clusterClass(): string
    {
        return LogisticsCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/logistics';
    }
}
