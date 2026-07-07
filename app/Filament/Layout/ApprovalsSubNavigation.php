<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\ApprovalsCluster;

class ApprovalsSubNavigation extends ClusterSubNavigation
{
    public static function isApprovalsPath(?string $path = null): bool
    {
        return static::isModulePath($path);
    }

    public static function clusterClass(): string
    {
        return ApprovalsCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/approvals';
    }
}
