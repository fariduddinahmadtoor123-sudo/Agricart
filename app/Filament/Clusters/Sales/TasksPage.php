<?php

namespace App\Filament\Clusters\Sales;

use App\Filament\Clusters\SalesCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class TasksPage extends BaseModulePage
{
    protected static ?string $cluster = SalesCluster::class;

    protected static ?string $navigationLabel = 'Tasks';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'tasks';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    /**
     * Staff task inbox is unused for now; keep the route for deep links / future use.
     */
    protected static bool $shouldRegisterNavigation = false;

    public static function registersInModuleChrome(): bool
    {
        return false;
    }

    protected string $view = 'filament.pages.sales.tasks';

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
