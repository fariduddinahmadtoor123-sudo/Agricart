<?php

namespace App\Services\Staff;

use App\Models\User;
use App\Services\Access\ModulePageAccess;
use App\Services\Access\PermissionRegistry;

class StaffPermissions
{
    public static function canAccessModule(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermission('staff.view_self')) {
            return true;
        }

        return ModulePageAccess::canAccessModule(
            $user,
            'staff',
            PermissionRegistry::pageSlugs('staff'),
        );
    }

    /** Full employee roster (HR / managers). */
    public static function canViewRoster(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return ModulePageAccess::can($user, 'staff', 'staff', 'view');
    }

    /** Own attendance, payroll, leave, advances (and own row when roster is restricted). */
    public static function canUseSelfService(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('staff.view_self')
            || ModulePageAccess::can($user, 'staff', 'staff', 'view');
    }

    public static function canManageRoster(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return ModulePageAccess::can($user, 'staff', 'staff', 'create')
            || ModulePageAccess::can($user, 'staff', 'staff', 'edit');
    }

    public static function canViewAllAttendance(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('staff.attendance.view_all')
            || ModulePageAccess::can($user, 'staff', 'attendance', 'view');
    }

    public static function canViewAllPayroll(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('staff.payroll.view_all')
            || ModulePageAccess::can($user, 'staff', 'payroll', 'view');
    }

    public static function canRunPayroll(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('staff.payroll.view_all')
            || ModulePageAccess::can($user, 'staff', 'payroll', 'edit');
    }

    public static function canViewAllLeave(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('staff.leave.view_all')
            || ModulePageAccess::can($user, 'staff', 'leave-management', 'view');
    }

    public static function canViewAllAdvances(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('staff.advances.view_all')
            || ModulePageAccess::can($user, 'staff', 'advances', 'view');
    }

    public static function canMarkAttendance(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('staff.attendance.mark')
            || ModulePageAccess::can($user, 'staff', 'attendance', 'edit');
    }

    public static function canManageLeaveSettings(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('staff.leave.view_all')
            || ModulePageAccess::can($user, 'staff', 'leave-management', 'view')
            || ModulePageAccess::can($user, 'staff', 'leave-management', 'edit');
    }

    public static function canManageAdvances(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('staff.advances.manage')
            || ModulePageAccess::can($user, 'staff', 'advances', 'create')
            || ModulePageAccess::can($user, 'staff', 'advances', 'edit');
    }
}
