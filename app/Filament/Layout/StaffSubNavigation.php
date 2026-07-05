<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\StaffCluster;

class StaffSubNavigation extends ClusterSubNavigation
{
    protected static function clusterClass(): string
    {
        return StaffCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/staff';
    }
}
