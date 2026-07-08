<?php

namespace App\Services\Staff;

use App\Models\User;

class StaffPermissions
{
    public static function canAccessModule(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasAnyPermission([
            'staff.view',
            'staff.view_self',
        ]);
    }

    /** Full employee roster (HR / managers). */
    public static function canViewRoster(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('staff.view');
    }

    /** Own attendance, payroll, leave, advances (and own row when roster is restricted). */
    public static function canUseSelfService(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasAnyPermission([
            'staff.view',
            'staff.view_self',
        ]);
    }

    public static function canManageRoster(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasAnyPermission([
            'staff.create',
            'staff.edit',
        ]);
    }

    public static function canViewAllAttendance(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasAnyPermission([
            'staff.view',
            'staff.attendance.view_all',
        ]);
    }

    public static function canViewAllPayroll(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasAnyPermission([
            'staff.view',
            'staff.payroll.view_all',
        ]);
    }

    public static function canViewAllLeave(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasAnyPermission([
            'staff.view',
            'staff.leave.view_all',
        ]);
    }

    public static function canViewAllAdvances(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasAnyPermission([
            'staff.view',
            'staff.advances.view_all',
        ]);
    }

    public static function canMarkAttendance(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasAnyPermission([
            'staff.attendance.mark',
            'staff.edit',
        ]);
    }

    public static function canManageLeaveSettings(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasAnyPermission([
            'staff.view',
            'staff.leave.view_all',
            'staff.edit',
        ]);
    }
}
