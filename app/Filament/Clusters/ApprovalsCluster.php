<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ApprovalsCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'Approvals';

    protected static ?string $clusterBreadcrumb = 'Approvals';

    protected static ?string $slug = 'approvals';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
}
