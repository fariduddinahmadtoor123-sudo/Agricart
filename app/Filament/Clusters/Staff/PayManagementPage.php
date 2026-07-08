<?php

namespace App\Filament\Clusters\Staff;

use App\Filament\Clusters\StaffCluster;
use App\Filament\Pages\BaseModulePage;
use App\Models\User;
use App\Services\Staff\StaffPermissions;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class PayManagementPage extends BaseModulePage
{
    protected static ?string $cluster = StaffCluster::class;

    protected static ?string $navigationLabel = 'Pay Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'pay-management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected string $view = 'filament.pages.staff.pay-management';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->canUseAdmin()) {
            return false;
        }

        return StaffPermissions::canViewRoster($user);
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
        return StaffCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
