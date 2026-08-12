<?php

namespace App\Services\Access;

use App\Filament\Layout\ClusterSubNavigation;
use App\Filament\Layout\ModuleChrome;
use Filament\Facades\Filament;

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
            'dashboard' => [
                'en' => 'Overview, summary, and staff notes.',
                'ur' => 'جائزہ، خلاصہ، اور عملے کے نوٹس۔',
            ],
            'approvals' => [
                'en' => 'Staff and supplier applications waiting for review.',
                'ur' => 'عملہ اور سپلائر کی درخواستیں جو جائزے کے منتظر ہیں۔',
            ],
            'contacts' => [
                'en' => 'Customers, suppliers, transport / goods companies, and message templates.',
                'ur' => 'گاہک، سپلائرز، ٹرانسپورٹ / گڈز کمپنیاں، اور میسج ٹیمپلیٹس۔',
            ],
            'catalog' => [
                'en' => 'One Catalog role covers products, brands, categories, units, packing units, attributes, controls, labels, and Agricart product reviews. View alone cannot Add — also enable Create (and Edit to change records).',
                'ur' => 'ایک کیٹلاگ رول میں پروڈکٹس، برانڈز، زمرے، یونٹس، پیکنگ یونٹس، خصوصیات، کنٹرولز، لیبلز اور ایگری کارٹ پروڈکٹ ریویوز شامل ہیں۔ صرف دیکھیں سے Add نہیں ہوتا — بنائیں (اور ترمیم) بھی آن کریں۔',
            ],
            'purchasing' => [
                'en' => 'Purchases, planning, quotations, payment summary, re-orders, price tags, and price update. Channel Price Control is under Channels Hub.',
                'ur' => 'خریداری، منصوبہ بندی، کوٹیشنز، ادائیگی سمری، ری آرڈرز، قیمت ٹیگز، اور پرائس اپڈیٹ۔ چینل پرائس کنٹرول چینلز حب میں ہے۔',
            ],
            'sales' => [
                'en' => 'Sales & POS, quotations, ready-for-queue orders, dispatch, forwarded orders, returns, and tasks.',
                'ur' => 'سیلز اینڈ پی او ایس، کوٹیشنز، کیو کے آرڈرز، ڈسپیچ، فارورڈڈ، ریٹرنز، اور ٹاسکس۔',
            ],
            'store' => [
                'en' => 'Online Store covers layout settings, customer messages, customer product reviews moderation, and store items. Visitor analytics has its own checkbox below.',
                'ur' => 'آن لائن اسٹور میں لے آؤٹ، کسٹمر میسجز، کسٹمر پروڈکٹ ریویوز کی منظوری، اور اسٹور آئٹمز شامل ہیں۔ وزیٹر اینالیٹکس الگ چیک باکس ہے۔',
            ],
            'logistics' => [
                'en' => 'Delivery methods, pending shipments, and shipped orders.',
                'ur' => 'ڈیلیوری طریقے، زیر التوا شپمنٹس، اور بھیجے گئے آرڈرز۔',
            ],
            'service' => [
                'en' => 'After-sales service requests.',
                'ur' => 'بعد از فروخت سروس کی درخواستیں۔',
            ],
            'reports' => [
                'en' => 'Stock, ledgers, sales, profit & loss, expenses, cash, and seasonal reports.',
                'ur' => 'اسٹاک، لیجرز، سیلز، منافع و نقصان، اخراجات، کیش، اور موسمی رپورٹس۔',
            ],
            'staff' => [
                'en' => 'Staff roster, pay, attendance, advances, payroll, and leave. Extra checkboxes below control self-service vs all-staff records.',
                'ur' => 'عملے کی فہرست، پے، حاضری، ایڈوانس، پے رول، اور رخصت۔ نیچے اضافی چیک باکس ذاتی سروس بمقابلہ تمام عملہ کنٹرول کرتے ہیں۔',
            ],
            'finance' => [
                'en' => 'Banks, fund transfers, expense categories, expenses, payments, cash book, and financial reports.',
                'ur' => 'بینک، فنڈ ٹرانسفر، اخراجات کے زمرے، اخراجات، ادائیگیاں، کیش بک، اور مالی رپورٹس۔',
            ],
            'marketplace' => [
                'en' => 'Sales channels, price control, quantity control, and secret coding.',
                'ur' => 'سیلز چینلز، پرائس کنٹرول، کوانٹٹی کنٹرول، اور خفیہ کوڈنگ۔',
            ],
            'settings' => [
                'en' => 'Profile, tax, media, watermarks, AI, printing, access, users, backups, errors, and activity. Extra checkboxes below are required for those settings pages.',
                'ur' => 'پروفائل، ٹیکس، میڈیا، واٹرمارک، AI، پرنٹ، رسائی، صارفین، بیک اپ، ایررز، اور ایکٹیویٹی۔ ان سیٹنگز صفحات کے لیے نیچے اضافی چیک باکس لازمی ہیں۔',
            ],
            'docs' => [
                'en' => 'In-app user guide. Inner sections still follow each module’s View permission.',
                'ur' => 'ایپ کے اندر یوزر گائیڈ۔ اندرونی حصے پھر بھی ہر ماڈیول کی دیکھیں اجازت کے مطابق فلٹر ہوتے ہیں۔',
            ],
        ];

        $moduleActionLabels = [
            'catalog' => [
                'view' => ['View Catalog (browse products, brands, categories…)', 'کیٹلاگ دیکھیں (پروڈکٹس، برانڈز، زمرے…)'],
                'create' => ['Create Catalog items (Add Product, Brand, Category…)', 'کیٹلاگ بنائیں (پروڈکٹ، برانڈ، زمرہ شامل کریں…)'],
                'edit' => ['Edit Catalog items & Agricart product reviews', 'کیٹلاگ ترمیم اور ایگری کارٹ پروڈکٹ ریویوز'],
                'delete' => ['Delete Catalog items', 'کیٹلاگ آئٹمز حذف'],
            ],
            'purchasing' => [
                'view' => ['View Purchasing (purchases, planning, quotations, price tags, price update…)', 'خریداری دیکھیں (خرید، منصوبہ بندی، کوٹیشنز، قیمت ٹیگز، پرائس اپڈیٹ…)'],
                'create' => ['Create Purchasing records', 'خریداری ریکارڈ بنائیں'],
                'edit' => ['Edit Purchasing records & price update', 'خریداری ریکارڈ اور پرائس اپڈیٹ ترمیم'],
                'delete' => ['Delete Purchasing records', 'خریداری ریکارڈ حذف'],
            ],
            'marketplace' => [
                'view' => ['View Channels Hub (sales channels, price control, quantity, secret coding)', 'چینلز حب دیکھیں (سیلز چینلز، پرائس کنٹرول، مقدار، خفیہ کوڈنگ)'],
                'create' => ['Create Channels Hub records', 'چینلز حب ریکارڈ بنائیں'],
                'edit' => ['Edit Channels Hub (including price control)', 'چینلز حب ترمیم (بشمول پرائس کنٹرول)'],
                'delete' => ['Delete Channels Hub records', 'چینلز حب ریکارڈ حذف'],
            ],
            'contacts' => [
                'view' => ['View Contacts (customers, suppliers, transport / goods…)', 'رابطے دیکھیں (گاہک، سپلائرز، ٹرانسپورٹ / گڈز…)'],
                'create' => ['Create Contacts', 'رابطے بنائیں'],
                'edit' => ['Edit Contacts', 'رابطے ترمیم'],
                'delete' => ['Delete Contacts', 'رابطے حذف'],
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

                if (isset($moduleHints[$key])) {
                    $groups[$key]['hint_en'] = $moduleHints[$key]['en'];
                    $groups[$key]['hint_ur'] = $moduleHints[$key]['ur'];
                }

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
            'hint_en' => 'Optional channels (support, notes, tasks). Private Messenger between staff is always on for every active admin — it is not controlled by these toggles.',
            'hint_ur' => 'اختیاری چینلز (سپورٹ، نوٹس، ٹاسکس)۔ اسٹاف پرائیویٹ میسنجر ہر ایکٹو ایڈمن کے لیے ہمیشہ کھلا ہے — ان پرمیشنز سے نہیں بند ہوتا۔',
            'permissions' => [
                ['key' => 'comms.access', 'label_en' => 'Access Communication Platform', 'label_ur' => 'مواصلاتی پلیٹ فارم رسائی'],
                ['key' => 'comms.channel.customer_support.access', 'label_en' => 'Customer Support channel', 'label_ur' => 'کسٹمر سپورٹ چینل'],
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
     * Groups plus the live chrome page list for the Access form.
     *
     * @return array<string, array{label_en: string, label_ur: string, hint_en?: string, hint_ur?: string, pages?: list<string>, permissions: list<array{key: string, label_en: string, label_ur: string}>}>
     */
    public static function groupsForForm(): array
    {
        $groups = self::groups();

        foreach ($groups as $key => &$group) {
            $pages = self::pagesForModule($key);

            if ($pages !== []) {
                $group['pages'] = $pages;
            }
        }

        unset($group);

        return $groups;
    }

    /**
     * @return list<string>
     */
    public static function pagesForModule(string $moduleKey): array
    {
        $module = collect(ModuleChrome::modules())->firstWhere('key', $moduleKey);

        if (! is_array($module)) {
            return [];
        }

        $subNav = $module['subNavigation'] ?? null;

        if (! is_string($subNav) || ! class_exists($subNav) || ! is_subclass_of($subNav, ClusterSubNavigation::class)) {
            return [];
        }

        try {
            if (Filament::getCurrentPanel() === null) {
                Filament::setCurrentPanel(Filament::getPanel('admin'));
            }

            return $subNav::chromePageLabels();
        } catch (\Throwable) {
            return [];
        }
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

    /**
     * Ensure every registry permission exists in the database (safe to call often).
     *
     * @return list<int> Permission IDs
     */
    public static function syncToDatabase(): array
    {
        $ids = [];

        foreach (self::flatPermissions() as $definition) {
            $permission = \App\Models\Permission::query()->updateOrCreate(
                ['key' => $definition['key']],
                [
                    'group_key' => $definition['group_key'],
                    'label_en' => $definition['label_en'],
                    'label_ur' => $definition['label_ur'],
                    'sort_order' => $definition['sort_order'],
                ],
            );

            $ids[] = (int) $permission->id;
        }

        return $ids;
    }
}
