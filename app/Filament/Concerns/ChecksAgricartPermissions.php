<?php

namespace App\Filament\Concerns;

use App\Filament\Clusters\BaseAgricartCluster;
use App\Models\User;
use App\Services\Access\ModulePageAccess;
use App\Services\Access\PermissionRegistry;

trait ChecksAgricartPermissions
{
    protected static function requiredPermission(): ?string
    {
        $cluster = static::getCluster();

        if ($cluster === null) {
            return null;
        }

        $moduleSlug = is_subclass_of($cluster, BaseAgricartCluster::class)
            ? $cluster::moduleSlug()
            : '';

        if (
            $moduleSlug !== ''
            && PermissionRegistry::moduleUsesPagePermissions($moduleSlug)
            && filled(static::getSlug())
        ) {
            return ModulePageAccess::key($moduleSlug, (string) static::getSlug(), 'view');
        }

        return method_exists($cluster, 'viewPermission')
            ? $cluster::viewPermission()
            : null;
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

        return ModulePageAccess::userHasKey($user, $permission);
    }
}
