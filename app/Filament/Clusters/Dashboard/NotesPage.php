<?php

namespace App\Filament\Clusters\Dashboard;

use App\Filament\Clusters\DashboardCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class NotesPage extends BaseModulePage
{
    protected static ?string $cluster = DashboardCluster::class;

    protected static ?string $navigationLabel = 'Notes';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'notes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected string $view = 'filament.pages.dashboard.notes';

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
        return DashboardCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
