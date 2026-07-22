<?php

namespace App\Filament\Clusters\Sales;

use App\Filament\Clusters\SalesCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class InboxPage extends BaseModulePage
{
    protected static ?string $cluster = SalesCluster::class;

    protected static ?string $navigationLabel = 'Inbox';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'inbox';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    /**
     * Inbox lives in the topbar; keep the route for that link and deep links.
     */
    protected static bool $shouldRegisterNavigation = false;

    public static function registersInModuleChrome(): bool
    {
        return false;
    }

    protected string $view = 'filament.pages.sales.inbox';

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
