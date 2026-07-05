<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class DocsCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'Docs';

    protected static ?string $clusterBreadcrumb = 'Docs';

    protected static ?string $slug = 'docs';

    protected static ?int $navigationSort = 15;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;
}
