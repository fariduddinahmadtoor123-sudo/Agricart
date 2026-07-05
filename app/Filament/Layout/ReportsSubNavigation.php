<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\ReportsCluster;

class ReportsSubNavigation extends ClusterSubNavigation
{
    protected static function clusterClass(): string
    {
        return ReportsCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/reports';
    }
}
