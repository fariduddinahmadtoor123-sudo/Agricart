<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class FinanceCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'Finance';

    protected static ?string $clusterBreadcrumb = 'Finance';

    protected static ?string $slug = 'finance';

    protected static ?int $navigationSort = 12;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
}
