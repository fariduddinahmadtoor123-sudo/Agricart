<?php

namespace App\Filament\Clusters\Sales;

use App\Filament\Clusters\SalesCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class OnlineOrdersPage extends BaseModulePage
{
    protected static ?string $cluster = SalesCluster::class;

    protected static ?string $navigationLabel = 'Online Sale Orders';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'online-orders';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    /**
     * Holding step removed: Convert into Invoice now queues directly for dispatch.
     * Page kept for any legacy unqueued invoices (URL still works).
     */
    protected static bool $shouldRegisterNavigation = false;

    public static function registersInModuleChrome(): bool
    {
        return false;
    }

    protected string $view = 'filament.pages.sales.online-orders';

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
