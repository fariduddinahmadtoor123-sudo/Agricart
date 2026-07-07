<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\FinanceCluster;

class FinanceSubNavigation extends ClusterSubNavigation
{
    public static function clusterClass(): string
    {
        return FinanceCluster::class;
    }

    protected static function pathPrefix(): string
    {
        return 'admin/finance';
    }
}
