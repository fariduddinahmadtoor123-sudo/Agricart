<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\VaultCluster;

class VaultSubNavigation extends ClusterSubNavigation
{
    public static function clusterClass(): string
    {
        return VaultCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/vault';
    }
}
