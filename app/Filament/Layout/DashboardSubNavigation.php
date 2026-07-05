<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\DashboardCluster;

class DashboardSubNavigation extends ClusterSubNavigation
{
    protected static function clusterClass(): string
    {
        return DashboardCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/dashboard';
    }
}
