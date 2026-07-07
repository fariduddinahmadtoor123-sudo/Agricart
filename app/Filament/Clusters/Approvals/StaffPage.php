<?php

namespace App\Filament\Clusters\Approvals;

use App\Filament\Clusters\ApprovalsCluster;
use App\Filament\Pages\BaseModulePage;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class StaffPage extends BaseModulePage
{
    protected static ?string $cluster = ApprovalsCluster::class;

    protected static ?string $navigationLabel = 'Staff';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'staff';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected string $view = 'filament.pages.approvals.staff';

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
