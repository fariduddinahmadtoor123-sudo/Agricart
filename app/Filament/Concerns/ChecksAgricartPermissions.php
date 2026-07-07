<?php

namespace App\Filament\Concerns;

use App\Models\User;

trait ChecksAgricartPermissions
{
    protected static function requiredPermission(): ?string
    {
        $cluster = static::getCluster();

        if ($cluster === null) {
            return null;
        }

        $slug = $cluster::$slug ?? null;

        return filled($slug) ? "{$slug}.view" : null;
    }

    public static function canAccess(): bool
    {
        return static::userCanView();
    }

    protected static function userCanView(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->canUseAdmin()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $permission = static::requiredPermission();

        if ($permission === null) {
            return false;
        }

        return $user->hasPermission($permission);
    }
}
