<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\SettingsCluster;

class SettingsSubNavigation extends ClusterSubNavigation
{
    protected static function clusterClass(): string
    {
        return SettingsCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/settings';
    }
}
