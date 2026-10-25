<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\ToolsCluster;

class ToolsSubNavigation extends ClusterSubNavigation
{
    public static function clusterClass(): string
    {
        return ToolsCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/tools';
    }
}
