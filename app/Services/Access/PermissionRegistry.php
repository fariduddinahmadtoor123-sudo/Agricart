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
            'marketplace' => ['Pricing & Controls', 'قیمت و کنٹرول'],
            'settings' => ['Settings', 'ترتیبات'],
            'docs' => ['Docs', 'دستاویزات'],
        ];

        $groups = [];

        foreach ($modules as $key => [$labelEn, $labelUr]) {
            $permissions = [];

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
