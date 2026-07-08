<?php

namespace App\Services\Access;

class PermissionRegistry
{
    /**
     * @return array<string, array{label_en: string, label_ur: string, permissions: list<array{key: string, label_en: string, label_ur: string}>}>
     */
    public static function groups(): array
    {
        $actions = [
            'view' => ['View', 'دیکھیں'],
            'create' => ['Create', 'بنائیں'],
            'edit' => ['Edit', 'ترمیم'],
            'delete' => ['Delete', 'حذف'],
        ];

        $modules = [
            'dashboard' => ['Dashboard', 'ڈیش بورڈ'],
            'approvals' => ['Approvals', 'منظوریاں'],
            'contacts' => ['Contacts', 'رابطے'],
            'catalog' => ['Catalog', 'کیٹalog'],
            'purchasing' => ['Purchasing', 'خریداری'],
            'sales' => ['Sales', 'فروخت'],
            'store' => ['Online Store', 'آن لائن اسٹور'],
            'logistics' => ['Logistics', 'لاجسٹکس'],
            'service' => ['Service', 'سروس'],
            'reports' => ['Reports', 'رپورٹس'],
            'staff' => ['Staff', 'عملہ'],
            'finance' => ['Finance', 'مالیات'],
            'marketplace' => ['Channels Hub', 'چینلز حب'],
            'settings' => ['Settings', 'ترتیبات'],
            'docs' => ['Docs', 'دستاویزات'],
        ];

        $groups = [];

        foreach ($modules as $key => [$labelEn, $labelUr]) {
            $permissions = [];

            if ($key === 'staff') {
                $permissions = [
                    ['key' => 'staff.view', 'label_en' => 'View staff roster (all employees)', 'label_ur' => 'تمام عملہ کی فہرست دیکھیں'],
                    ['key' => 'staff.view_self', 'label_en' => 'Self-service (my attendance, payroll, leave)', 'label_ur' => 'ذاتی سروس (میری حاضری، تنخواہ، چھٹی)'],
                    ['key' => 'staff.create', 'label_en' => 'Add staff members', 'label_ur' => 'عملہ شامل کریں'],
                    ['key' => 'staff.edit', 'label_en' => 'Edit staff & activate/deactivate', 'label_ur' => 'عملہ ترمیم / فعال / غیر فعال'],
                    ['key' => 'staff.delete', 'label_en' => 'Delete staff records', 'label_ur' => 'عملہ حذف'],
                    ['key' => 'staff.attendance.view_all', 'label_en' => 'View all attendance records', 'label_ur' => 'سب کی حاضری دیکھیں'],
                    ['key' => 'staff.attendance.mark', 'label_en' => 'Mark daily attendance', 'label_ur' => 'روزانہ حاضری لگائیں'],
                    ['key' => 'staff.payroll.view_all', 'label_en' => 'View all payroll records', 'label_ur' => 'سب کی تنخواہ دیکھیں'],
                    ['key' => 'staff.leave.view_all', 'label_en' => 'View all leave records', 'label_ur' => 'سب کی چھٹیاں دیکھیں'],
                    ['key' => 'staff.advances.view_all', 'label_en' => 'View all advance records', 'label_ur' => 'سب کی ایڈوانس دیکھیں'],
                    ['key' => 'staff.advances.manage', 'label_en' => 'Record advances & repayments', 'label_ur' => 'ایڈوانس اور واپسی لکھیں'],
                ];

                $groups[$key] = [
                    'label_en' => $labelEn,
                    'label_ur' => $labelUr,
                    'permissions' => $permissions,
                ];

                continue;
            }

            foreach ($actions as $action => [$actionEn, $actionUr]) {
                $permissions[] = [
                    'key' => "{$key}.{$action}",
                    'label_en' => "{$actionEn} {$labelEn}",
                    'label_ur' => "{$labelUr} {$actionUr}",
                ];
            }

            $groups[$key] = [
                'label_en' => $labelEn,
                'label_ur' => $labelUr,
                'permissions' => $permissions,
            ];
        }

        $groups['settings']['permissions'][] = [
            'key' => 'settings.users.manage',
            'label_en' => 'Manage users',
            'label_ur' => 'یوزرز کا انتظام',
        ];
        $groups['settings']['permissions'][] = [
            'key' => 'settings.roles.manage',
            'label_en' => 'Manage roles & permissions',
            'label_ur' => 'رولز اور اجازتیں',
        ];
        $groups['settings']['permissions'][] = [
            'key' => 'settings.backups.manage',
            'label_en' => 'Manage backups & restore',
            'label_ur' => 'بیک اپ اور ریسٹور',
        ];
        $groups['settings']['permissions'][] = [
            'key' => 'settings.printing.manage',
            'label_en' => 'Manage print profiles',
            'label_ur' => 'پرنٹ پروفائلز کا انتظام',
        ];

        return $groups;
    }

    /**
     * @return list<array{key: string, group_key: string, label_en: string, label_ur: string, sort_order: int}>
     */
    public static function flatPermissions(): array
    {
        $flat = [];
        $sort = 0;

        foreach (self::groups() as $groupKey => $group) {
            foreach ($group['permissions'] as $permission) {
                $flat[] = [
                    'key' => $permission['key'],
                    'group_key' => $groupKey,
                    'label_en' => $permission['label_en'],
                    'label_ur' => $permission['label_ur'],
                    'sort_order' => $sort++,
                ];
            }
        }

        return $flat;
    }
}
