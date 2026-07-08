<?php

namespace App\Filament\Clusters;

use App\Models\User;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class StaffCluster extends BaseAgricartCluster
{
    protected static ?string $navigationLabel = 'Staff';

    protected static ?string $clusterBreadcrumb = 'Staff';

    protected static ?string $slug = 'staff';

    protected static ?int $navigationSort = 11;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->canUseAdmin()) {
            return false;
        }

        return \App\Services\Staff\StaffPermissions::canAccessModule($user);
    }
}
