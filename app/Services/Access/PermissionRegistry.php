<?php

namespace App\Services\Access;

class PermissionRegistry
{
    /**
     * @return array<string, array{label_en: string, label_ur: string, hint_en?: string, hint_ur?: string, permissions: list<array{key: string, label_en: string, label_ur: string}>}>
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
            'catalog' => ['Catalog', 'کیٹلاگ'],
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

        $moduleHints = [
            'catalog' => [
                'en' => 'One Catalog role covers products, brands, categories, units, attributes, controls, labels, and Agricart product reviews. View alone cannot Add — also enable Create (and Edit to change records).',
                'ur' => 'ایک کیٹلاگ رول میں پروڈکٹس، برانڈز، زمرے، یونٹس، خصوصیات، کنٹرولز، لیبلز اور ایگری کارٹ پروڈکٹ ریویوز شامل ہیں۔ صرف دیکھیں سے Add نہیں ہوتا — بنائیں (اور ترمیم) بھی آن کریں۔',
            ],
            'store' => [
                'en' => 'Online Store covers layout settings, customer messages, customer product reviews moderation, and store items. Visitor analytics has its own checkbox below.',
                'ur' => 'آن لائن اسٹور میں لے آؤٹ، کسٹمر میسجز، کسٹمر پروڈکٹ ریویوز کی منظوری، اور اسٹور آئٹمز شامل ہیں۔ وزیٹر اینالیٹکس الگ چیک باکس ہے۔',
            ],
        ];

        $moduleActionLabels = [
            'catalog' => [
                'view' => ['View Catalog (browse products, brands, categories…)', 'کیٹلاگ دیکھیں (پروڈکٹس، برانڈز، زمرے…)'],
                'create' => ['Create Catalog items (Add Product, Brand, Category…)', 'کیٹلاگ بنائیں (پروڈکٹ، برانڈ، زمرہ شامل کریں…)'],
                'edit' => ['Edit Catalog items & Agricart product reviews', 'کیٹلاگ ترمیم اور ایگری کارٹ پروڈکٹ ریویوز'],
                'delete' => ['Delete Catalog items', 'کیٹلاگ آئٹمز حذف'],
            ],
            'store' => [
                'view' => ['View Online Store (layout, messages, reviews, items)', 'آن لائن اسٹور دیکھیں (لے آؤٹ، میسجز، ریویوز، آئٹمز)'],
                'create' => ['Create Online Store content', 'آن لائن اسٹور مواد بنائیں'],
                'edit' => ['Edit store settings & moderate messages/reviews', 'اسٹور ترتیبات ترمیم اور میسجز/ریویوز منظوری'],
                'delete' => ['Delete Online Store content', 'آن لائن اسٹور مواد حذف'],
            ],
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
                if (isset($moduleActionLabels[$key][$action])) {
                    [$actionLabelEn, $actionLabelUr] = $moduleActionLabels[$key][$action];
                } else {
                    $actionLabelEn = "{$actionEn} {$labelEn}";
                    $actionLabelUr = "{$labelUr} {$actionUr}";
                }

                $permissions[] = [
                    'key' => "{$key}.{$action}",
                    'label_en' => $actionLabelEn,
                    'label_ur' => $actionLabelUr,
                ];
            }

            $group = [
                'label_en' => $labelEn,
                'label_ur' => $labelUr,
                'permissions' => $permissions,
            ];

            if (isset($moduleHints[$key])) {
                $group['hint_en'] = $moduleHints[$key]['en'];
                $group['hint_ur'] = $moduleHints[$key]['ur'];
            }

            $groups[$key] = $group;
        }

        $groups['store']['permissions'][] = [
            'key' => 'store.analytics.view',
            'label_en' => 'View visitor analytics',
            'label_ur' => 'وزیٹر اینالیٹکس دیکھیں',
        ];

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
        $groups['settings']['permissions'][] = [
            'key' => 'settings.ai.manage',
            'label_en' => 'Manage AI configuration',
            'label_ur' => 'اے آئی کنفیگریشن کا انتظام',
        ];
        $groups['settings']['permissions'][] = [
            'key' => 'settings.ai.logs.view',
            'label_en' => 'View AI activity logs',
            'label_ur' => 'اے آئی سرگرمی لاگز دیکھیں',
        ];
        $groups['settings']['permissions'][] = [
            'key' => 'settings.activity.view',
            'label_en' => 'View user activity logs',
            'label_ur' => 'یوزر ایکٹیویٹی لاگز دیکھیں',
        ];
        $groups['settings']['permissions'][] = [
            'key' => 'settings.system-errors.view',
            'label_en' => 'View sanitized system errors',
            'label_ur' => 'محفوظ سسٹم ایررز دیکھیں',
        ];
        $groups['settings']['permissions'][] = [
            'key' => 'settings.system-errors.manage',
            'label_en' => 'Resolve and manage system errors',
            'label_ur' => 'سسٹم ایررز حل اور مینج کریں',
        ];
        $groups['settings']['permissions'][] = [
            'key' => 'settings.media.view',
            'label_en' => 'View media library',
            'label_ur' => 'میڈیا لائبریری دیکھیں',
        ];
        $groups['settings']['permissions'][] = [
            'key' => 'settings.media.manage',
            'label_en' => 'Manage media library',
            'label_ur' => 'میڈیا لائبریری مینج کریں',
        ];

        $groups['comms'] = [
            'label_en' => 'Communication',
            'label_ur' => 'مواصلات',
            'hint_en' => 'Generic Communication Platform access. Channel keys are configurable — add new channels without changing core code.',
            'hint_ur' => 'عمومی مواصلاتی پلیٹ فارم۔ نئے چینلز کنفیگ/ڈیٹابیس سے شامل ہوں، کور کوڈ بدلے بغیر۔',
            'permissions' => [
                ['key' => 'comms.access', 'label_en' => 'Access Communication Platform', 'label_ur' => 'مواصلاتی پلیٹ فارم رسائی'],
                ['key' => 'comms.channel.customer_support.access', 'label_en' => 'Customer Support channel', 'label_ur' => 'کسٹمر سپورٹ چینل'],
                ['key' => 'comms.channel.staff_ops.access', 'label_en' => 'Staff Operations channel', 'label_ur' => 'اسٹاف آپریشنز چینل'],
                ['key' => 'comms.channel.supplier.access', 'label_en' => 'Supplier channel', 'label_ur' => 'سپلائر چینل'],
                ['key' => 'comms.channel.brand.access', 'label_en' => 'Brand channel', 'label_ur' => 'برانڈ چینل'],
                ['key' => 'comms.channel.internal_notes.access', 'label_en' => 'Internal Notes channel', 'label_ur' => 'اندرونی نوٹس چینل'],
                ['key' => 'comms.channel.system.access', 'label_en' => 'System Notifications channel', 'label_ur' => 'سسٹم نوٹیفکیشن چینل'],
                ['key' => 'comms.channel.task.access', 'label_en' => 'Task Communication channel', 'label_ur' => 'ٹاسک مواصلات چینل'],
                ['key' => 'comms.conversation.manage', 'label_en' => 'Manage conversations', 'label_ur' => 'گفتگو کا انتظام'],
                ['key' => 'comms.message.post', 'label_en' => 'Post messages', 'label_ur' => 'پیغامات بھیجیں'],
                ['key' => 'comms.attachment.upload', 'label_en' => 'Upload attachments', 'label_ur' => 'اٹیچمنٹس اپلوڈ'],
                ['key' => 'comms.link.manage', 'label_en' => 'Manage record links', 'label_ur' => 'ریکارڈ لنکس کا انتظام'],
                ['key' => 'comms.task.manage', 'label_en' => 'Manage communication tasks', 'label_ur' => 'مواصلاتی ٹاسکس کا انتظام'],
            ],
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
