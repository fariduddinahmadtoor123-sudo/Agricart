<?php

namespace App\Filament\Clusters\Staff;

use App\Filament\Clusters\StaffCluster;
use App\Filament\Pages\BasePlaceholderPage;
use App\Models\User;
use App\Services\Staff\StaffPermissions;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PayrollPage extends BasePlaceholderPage
{
    protected static ?string $cluster = StaffCluster::class;

    protected static ?string $navigationLabel = 'Payroll';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'payroll';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->canUseAdmin()) {
            return false;
        }

        return StaffPermissions::canAccessModule($user) && StaffPermissions::canUseSelfService($user);
    }
}
