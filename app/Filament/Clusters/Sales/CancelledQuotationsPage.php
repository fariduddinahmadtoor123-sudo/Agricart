<?php

namespace App\Filament\Clusters\Sales;

use App\Filament\Clusters\SalesCluster;
use App\Filament\Pages\BaseModulePage;
use App\Services\Access\ModulePageAccess;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class CancelledQuotationsPage extends BaseModulePage
{
    protected static ?string $cluster = SalesCluster::class;

    protected static ?string $navigationLabel = 'Cancelled Quotations';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'cancelled-quotations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected string $view = 'filament.pages.sales.cancelled-quotations';

    /**
     * Same staff who can open Online Quotations can browse the cancel archive.
     */
    protected static function requiredPermission(): ?string
    {
        return ModulePageAccess::key('sales', 'online-quotations', 'view');
    }

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
        return SalesCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
