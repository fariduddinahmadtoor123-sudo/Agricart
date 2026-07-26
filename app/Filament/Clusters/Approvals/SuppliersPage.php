<?php

namespace App\Filament\Clusters\Approvals;

use App\Filament\Clusters\ApprovalsCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class SuppliersPage extends BaseModulePage
{
    protected static ?string $cluster = ApprovalsCluster::class;

    protected static ?string $navigationLabel = 'Suppliers';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'suppliers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected string $view = 'filament.pages.approvals.suppliers';

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return ApprovalsCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
