<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\CatalogCluster;

class CatalogSubNavigation extends ClusterSubNavigation
{
    public static function isCatalogPath(?string $path = null): bool
    {
        return static::isModulePath($path);
    }

    public static function clusterClass(): string
    {
        return CatalogCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/catalog';
    }
}
