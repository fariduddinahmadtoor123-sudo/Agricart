<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\DocsCluster;

class DocsSubNavigation extends ClusterSubNavigation
{
    public static function clusterClass(): string
    {
        return DocsCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/docs';
    }
}
