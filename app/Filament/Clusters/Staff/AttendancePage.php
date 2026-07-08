<?php

namespace App\Filament\Clusters\Staff;

use App\Filament\Clusters\StaffCluster;
use App\Filament\Pages\BaseModulePage;
use App\Models\User;
use App\Services\Staff\StaffPermissions;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class AttendancePage extends BaseModulePage
{
    protected static ?string $cluster = StaffCluster::class;

    protected static ?string $navigationLabel = 'Attendance';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'attendance';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected string $view = 'filament.pages.staff.attendance';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->canUseAdmin()) {
            return false;
        }

        if (! StaffPermissions::canAccessModule($user)) {
            return false;
        }

        return StaffPermissions::canViewAllAttendance($user)
            || StaffPermissions::canMarkAttendance($user)
            || StaffPermissions::canUseSelfService($user);
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
