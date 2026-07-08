<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Staff\OverviewPage;
use App\Models\User;
use App\Services\Staff\StaffPermissions;

class StaffPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return StaffSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Staff';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'staff';
    }

    /**
     * @return array{
     *     addLabel: string|null,
     *     searchPlaceholder: string,
     *     resourceLabel: string,
     *     resourceSlug: string,
     *     formKey: string|null,
     *     filters: list<array{label: string, value: string}>,
     *     columns: list<array{label: string, value: string, visible: bool}>,
     * }|null
     */
    public static function config(): ?array
    {
        $config = parent::config();

        if ($config === null) {
            return null;
        }

        return match ($config['resourceSlug']) {
            'staff' => [
                ...$config,
                'addLabel' => static::userCan('create') || static::userCan('edit')
                    ? __('agricart.staff.members.actions.add')
                    : null,
                'livewirePrefix' => 'staff-members',
                'columnDataAttr' => 'data-staff-member-column',
                'interactive' => true,
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                ],
                'columns' => [
                    ['label' => __('agricart.staff.members.list.col_user_number'), 'value' => 'user_number', 'visible' => true],
                    ['label' => __('agricart.staff.members.list.col_name'), 'value' => 'name', 'visible' => true],
                    ['label' => __('agricart.staff.members.list.col_email'), 'value' => 'email', 'visible' => true],
                    ['label' => __('agricart.staff.members.list.col_role'), 'value' => 'role', 'visible' => true],
                    ['label' => __('agricart.staff.members.list.col_status'), 'value' => 'status', 'visible' => true],
                ],
                'formKey' => 'users',
                'formContext' => 'staff',
            ],
            'pay-management' => [
                ...$config,
                'addLabel' => static::userCan('edit') || static::userCan('create')
                    ? __('agricart.staff.pay_management.actions.assign')
                    : null,
                'livewirePrefix' => 'pay-management',
                'columnDataAttr' => 'data-pay-management-column',
                'interactive' => true,
                'hideFilters' => true,
                'filters' => [],
                'columns' => [
                    ['label' => __('agricart.staff.pay_management.list.col_user_number'), 'value' => 'user_number', 'visible' => true],
                    ['label' => __('agricart.staff.pay_management.list.col_name'), 'value' => 'name', 'visible' => true],
                    ['label' => __('agricart.staff.pay_management.list.col_monthly_gross'), 'value' => 'monthly_gross', 'visible' => true],
                    ['label' => __('agricart.staff.pay_management.list.col_effective_from'), 'value' => 'effective_from', 'visible' => true],
                    ['label' => __('agricart.staff.pay_management.list.col_status'), 'value' => 'status', 'visible' => true],
                ],
                'formKey' => 'staff-salary',
            ],
            'attendance' => [
                ...$config,
                'addLabel' => static::userCanMarkAttendance()
                    ? __('agricart.staff.attendance.actions.mark_daily')
                    : null,
                'compactAddButton' => true,
                'livewirePrefix' => 'staff-attendance',
                'columnDataAttr' => 'data-staff-attendance-column',
                'interactive' => true,
                'filters' => [
                    ['label' => __('agricart.staff.attendance.filters.all'), 'value' => 'all'],
                    ['label' => __('agricart.staff.attendance.statuses.present'), 'value' => 'status:present'],
                    ['label' => __('agricart.staff.attendance.statuses.on_paid_leave'), 'value' => 'status:on_paid_leave'],
                    ['label' => __('agricart.staff.attendance.statuses.public_holiday'), 'value' => 'status:public_holiday'],
                    ['label' => __('agricart.staff.attendance.statuses.absent_unpaid'), 'value' => 'status:absent_unpaid'],
                ],
                'columns' => [
                    ['label' => __('agricart.staff.members.list.col_user_number'), 'value' => 'user_number', 'visible' => true],
                    ['label' => __('agricart.staff.members.list.col_name'), 'value' => 'name', 'visible' => true],
                    ['label' => __('agricart.staff.members.list.col_role'), 'value' => 'role', 'visible' => true],
                    ['label' => __('agricart.staff.attendance.list.col_status'), 'value' => 'attendance_status', 'visible' => true],
                    ['label' => __('agricart.staff.attendance.list.col_paid_day'), 'value' => 'paid_day', 'visible' => true],
                ],
                'formKey' => 'staff-attendance',
            ],
            default => $config,
        };
    }

    protected static function userCanMarkAttendance(): bool
    {
        $user = auth()->user();

        return $user instanceof User && StaffPermissions::canMarkAttendance($user);
    }
}
