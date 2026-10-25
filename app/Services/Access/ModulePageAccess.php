<?php

namespace App\Services\Access;

use App\Models\User;

/**
 * Page-scoped permissions (e.g. purchasing.price-update.edit) with legacy
 * module-level fallback (purchasing.edit).
 */
final class ModulePageAccess
{
    public static function key(string $module, string $page, string $action): string
    {
        return "{$module}.{$page}.{$action}";
    }

    public static function can(User $user, string $module, string $page, string $action): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $key = self::key($module, $page, $action);

        if ($user->hasPermission($key)) {
            return true;
        }

        foreach (self::legacyGrantsForExpandedKey($key) as $grant) {
            if ($user->hasPermission($grant)) {
                return true;
            }
        }

        // Legacy module-wide grant still covers every page for that action.
        return $user->hasPermission("{$module}.{$action}");
    }

    /**
     * True when the user may open the module chrome (any page view, or legacy view).
     *
     * @param  list<string>  $pageSlugs
     */
    public static function canAccessModule(User $user, string $module, array $pageSlugs): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermission("{$module}.view")) {
            return true;
        }

        foreach ($pageSlugs as $page) {
            if ($user->hasPermission(self::key($module, $page, 'view'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve a concrete permission key, honouring legacy module grants.
     */
    public static function userHasKey(User $user, string $key): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermission($key)) {
            return true;
        }

        if (preg_match('/^([a-z]+)\.([a-z0-9-]+)\.([a-z_]+)$/', $key, $matches) === 1) {
            return $user->hasPermission("{$matches[1]}.{$matches[3]}");
        }

        // Legacy module.action (e.g. sales.view) after page-scoped migration.
        if (
            preg_match('/^([a-z]+)\.(view|create|edit|delete|print)$/', $key, $matches) === 1
            && PermissionRegistry::moduleUsesPagePermissions($matches[1])
        ) {
            foreach (array_keys(PermissionRegistry::pagesFor($matches[1])) as $page) {
                if (self::can($user, $matches[1], $page, $matches[2])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function legacyGrantsForExpandedKey(string $expandedKey): array
    {
        return self::invertedLegacyExpansionMap()[$expandedKey] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    private static function invertedLegacyExpansionMap(): array
    {
        /** @var array<string, list<string>>|null */
        static $inverted = null;

        if ($inverted !== null) {
            return $inverted;
        }

        $inverted = [];

        foreach (PermissionRegistry::legacyFineGrainedExpansionMap() as $grant => $expandedKeys) {
            foreach ($expandedKeys as $expanded) {
                $inverted[$expanded][] = $grant;
            }
        }

        return $inverted;
    }
}
