<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ToolsCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'External Apps';

    protected static ?string $clusterBreadcrumb = 'External Apps';

    protected static ?string $slug = 'tools';

    protected static ?int $navigationSort = 16;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCubeTransparent;
}
