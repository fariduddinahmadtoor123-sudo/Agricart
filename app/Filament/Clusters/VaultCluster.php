<?php

namespace App\Filament\Clusters;

use App\Filament\Clusters\Vault\MyBoxPage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class VaultCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'Personal Vault';

    protected static ?string $clusterBreadcrumb = 'Personal Vault';

    protected static ?string $slug = 'vault';

    protected static ?int $navigationSort = 16;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    /**
     * @return class-string|null
     */
    protected static function resolveOverviewPageClass(): ?string
    {
        return MyBoxPage::class;
    }
}
