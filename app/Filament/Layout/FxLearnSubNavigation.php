<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\FxLearnCluster;

class FxLearnSubNavigation extends ClusterSubNavigation
{
    public static function clusterClass(): string
    {
        return FxLearnCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/fx-learn';
    }
}
