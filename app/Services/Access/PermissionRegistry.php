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
                'en' => 'Grant each Dashboard page separately (Overview, Summary, Notes).',
                'ur' => 'ڈیش بورڈ کا ہر صفحہ الگ دیں (جائزہ، خلاصہ، نوٹس)۔',
            ],
            'approvals' => [
                'en' => 'Grant each Approvals page separately (staff and supplier applications).',
                'ur' => 'منظوریوں کا ہر صفحہ الگ دیں (عملہ اور سپلائر درخواستیں)۔',
            ],
            'contacts' => [
                'en' => 'Grant each Contacts page separately. View alone cannot Add — also enable Create (and Edit to change records).',
                'ur' => 'رابطوں کا ہر صفحہ الگ دیں۔ صرف دیکھیں سے Add نہیں ہوتا — بنائیں (اور ترمیم) بھی آن کریں۔',
            ],
            'catalog' => [
                'en' => 'Grant each Catalog page separately (products, brands, categories…). View alone cannot Add — also enable Create (and Edit to change records).',
                'ur' => 'کیٹلاگ کا ہر صفحہ الگ دیں (پروڈکٹس، برانڈز، زمرے…)۔ صرف دیکھیں سے Add نہیں ہوتا — بنائیں (اور ترمیم) بھی آن کریں۔',
            ],
            'purchasing' => [
                'en' => 'Grant each Purchasing page separately (View / Create / Edit / Delete / Print where available). Example: Price Update alone does not open Purchases. Channel Price Control is under Channels Hub.',
                'ur' => 'خریداری کا ہر صفحہ الگ دیں (دیکھیں / بنائیں / ترمیم / حذف / پرنٹ جہاں ہو)۔ مثال: صرف پرائس اپڈیٹ سے Purchases نہیں کھلتے۔ چینل پرائس کنٹرول چینلز حب میں ہے۔',
            ],
            'sales' => [
                'en' => 'Grant each Sales page separately (POS, quotations, dispatch, returns…). View alone cannot Add — also enable Create where needed.',
                'ur' => 'سیلز کا ہر صفحہ الگ دیں (پی او ایس، کوٹیشنز، ڈسپیچ، ریٹرنز…)۔ صرف دیکھیں سے Add نہیں ہوتا — جہاں چاہیے بنائیں بھی آن کریں۔',
            ],
            'store' => [
                'en' => 'Grant each Online Store page separately. Agricart Inbox (customer messages and the bell badge) is listed first after Overview.',
                'ur' => 'آن لائن اسٹور کا ہر صفحہ الگ دیں۔ ایگری کارٹ ان باکس (کسٹمر میسجز اور گھنٹی) جائزہ کے بعد سب سے پہلے ہے۔',
            ],
            'logistics' => [
                'en' => 'Grant each Logistics page separately (delivery methods, pending, shipped, delivered).',
                'ur' => 'لاجسٹکس کا ہر صفحہ الگ دیں (ڈیلیوری طریقے، زیر التوا، بھیجے گئے، ڈلیورڈ)۔',
            ],
            'service' => [
                'en' => 'Grant each Service page separately.',
                'ur' => 'سروس کا ہر صفحہ الگ دیں۔',
            ],
            'reports' => [
                'en' => 'Grant each Reports page separately (stock, ledgers, P&L, seasonal…).',
                'ur' => 'رپورٹس کا ہر صفحہ الگ دیں (اسٹاک، لیجرز، منافع و نقصان، موسمی…)۔',
            ],
            'staff' => [
                'en' => 'Grant each Staff page separately. Extra checkboxes below control self-service vs all-staff records.',
                'ur' => 'عملے کا ہر صفحہ الگ دیں۔ نیچے اضافی چیک باکس ذاتی سروس بمقابلہ تمام عملہ کنٹرول کرتے ہیں۔',
            ],
            'finance' => [
                'en' => 'Grant each Finance page separately (banks, transfers, expenses, cash book…).',
                'ur' => 'مالیات کا ہر صفحہ الگ دیں (بینک، ٹرانسفر، اخراجات، کیش بک…)۔',
            ],
            'marketplace' => [
                'en' => 'Grant each Channels Hub page separately (sales channels, price control, quantity, secret coding).',
                'ur' => 'چینلز حب کا ہر صفحہ الگ دیں (سیلز چینلز، پرائس کنٹرول، مقدار، خفیہ کوڈنگ)۔',
            ],
            'settings' => [
                'en' => 'Grant each Settings page separately (profile, tax, media, AI, users, backups…).',
                'ur' => 'ترتیبات کا ہر صفحہ الگ دیں (پروفائل، ٹیکس، میڈیا، AI، یوزرز، بیک اپ…)۔',
            ],
            'docs' => [
                'en' => 'Grant Docs pages separately. Inner guide sections still follow each module’s page View permission.',
                'ur' => 'دستاویزات کے صفحات الگ دیں۔ اندرونی گائیڈ حصے پھر بھی ہر ماڈیول کی پیج دیکھیں اجازت کے مطابق فلٹر ہوتے ہیں۔',
            ],
        ];

        $groups = [];

        foreach ($modules as $key => [$labelEn, $labelUr]) {
            $hint = $moduleHints[$key] ?? ['en' => '', 'ur' => ''];
            $groups[$key] = self::pageScopedGroup($key, $labelEn, $labelUr, $hint);

            if ($key === 'staff') {
                foreach (self::staffExtraPermissions() as $extra) {
                    $groups[$key]['permissions'][] = $extra;
                }
            }
        }

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
     * Groups plus nested page resources for the Access form.
     *
     * @return array<string, array{label_en: string, label_ur: string, hint_en?: string, hint_ur?: string, pages?: list<string>, resources?: list<array{slug: string, label_en: string, label_ur: string, permissions: list<array{key: string, label_en: string, label_ur: string}>}>, extras?: list<array{key: string, label_en: string, label_ur: string}>, permissions: list<array{key: string, label_en: string, label_ur: string}>}>
     */
    public static function groupsForForm(): array
    {
        $groups = self::groups();

        foreach ($groups as $key => &$group) {
            if (! self::moduleUsesPagePermissions($key)) {
                $pages = self::pagesForModule($key);

                if ($pages !== []) {
                    $group['pages'] = $pages;
                }

                continue;
            }

            $group['resources'] = self::resourcesForForm($key);
            unset($group['pages']);

            if ($key === 'staff') {
                $group['extras'] = self::staffExtraPermissions();
            }
        }

        unset($group);

        return $groups;
    }

    /**
     * Page definitions per chrome module: slug => labels + actions.
     *
     * @return array<string, array<string, array{label_en: string, label_ur: string, actions: list<string>}>>
     */
    public static function modulePages(): array
    {
        $crud = ['view', 'create', 'edit', 'delete'];
        $crudPrint = ['view', 'create', 'edit', 'delete', 'print'];
        $viewOnly = ['view'];
        $viewEdit = ['view', 'edit'];
        $viewPrint = ['view', 'print'];

        return [
            'dashboard' => [
                'overview' => ['label_en' => 'Overview', 'label_ur' => 'جائزہ', 'actions' => $viewOnly],
                'summary' => ['label_en' => 'Summary', 'label_ur' => 'خلاصہ', 'actions' => $viewOnly],
                'notes' => ['label_en' => 'Notes', 'label_ur' => 'نوٹس', 'actions' => $crudPrint],
            ],
            'approvals' => [
                'overview' => ['label_en' => 'Overview', 'label_ur' => 'جائزہ', 'actions' => $viewOnly],
                'staff' => ['label_en' => 'Staff', 'label_ur' => 'عملہ', 'actions' => $viewEdit],
                'suppliers' => ['label_en' => 'Suppliers', 'label_ur' => 'سپلائرز', 'actions' => $viewEdit],
            ],
            'contacts' => [
                'overview' => ['label_en' => 'Overview', 'label_ur' => 'جائزہ', 'actions' => $viewOnly],
                'customers' => ['label_en' => 'Customers', 'label_ur' => 'گاہک', 'actions' => $crud],
                'suppliers' => ['label_en' => 'Suppliers', 'label_ur' => 'سپلائرز', 'actions' => $crudPrint],
                'transport-companies' => ['label_en' => 'Transport / Goods', 'label_ur' => 'ٹرانسپورٹ / گڈز', 'actions' => $crud],
                'message-templates' => ['label_en' => 'Message Templates', 'label_ur' => 'میسج ٹیمپلیٹس', 'actions' => $viewEdit],
            ],
            'catalog' => [
                'overview' => ['label_en' => 'Overview', 'label_ur' => 'جائزہ', 'actions' => $viewOnly],
                'products' => ['label_en' => 'Products', 'label_ur' => 'پروڈکٹس', 'actions' => $crud],
                'brands' => ['label_en' => 'Brands', 'label_ur' => 'برانڈز', 'actions' => $crudPrint],
                'categories' => ['label_en' => 'Categories', 'label_ur' => 'زمرے', 'actions' => $crud],
                'units' => ['label_en' => 'Units', 'label_ur' => 'یونٹس', 'actions' => $crud],
                'packing-units' => ['label_en' => 'Packing Units', 'label_ur' => 'پیکنگ یونٹس', 'actions' => $crud],
                'attributes' => ['label_en' => 'Attributes', 'label_ur' => 'خصوصیات', 'actions' => $crud],
                'controls' => ['label_en' => 'Controls', 'label_ur' => 'کنٹرولز', 'actions' => $crud],
                'labels' => ['label_en' => 'Labels', 'label_ur' => 'لیبلز', 'actions' => ['view', 'create', 'print']],
                'product-reviews' => ['label_en' => 'Product Reviews', 'label_ur' => 'پروڈکٹ ریویوز', 'actions' => $viewEdit],
            ],
            'purchasing' => [
                'overview' => ['label_en' => 'Overview', 'label_ur' => 'جائزہ', 'actions' => $viewOnly],
                'purchase-planning' => ['label_en' => 'Purchase Planning', 'label_ur' => 'خریداری منصوبہ', 'actions' => $crudPrint],
                'purchase-quotations' => ['label_en' => 'Purchase Quotations', 'label_ur' => 'خریداری کوٹیشنز', 'actions' => $crudPrint],
                'purchases' => ['label_en' => 'Purchases', 'label_ur' => 'خریداریاں', 'actions' => $crudPrint],
                'purchase-payment-summary' => ['label_en' => 'Payment Summary', 'label_ur' => 'ادائیگی سمری', 'actions' => $crudPrint],
                're-order-center' => ['label_en' => 'Re-Order Center', 'label_ur' => 'ری آرڈر سینٹر', 'actions' => ['view', 'create', 'print']],
                'price-tags' => ['label_en' => 'Price Tags', 'label_ur' => 'قیمت ٹیگز', 'actions' => $crudPrint],
                'rate-list' => ['label_en' => 'Rate List', 'label_ur' => 'ریٹ لسٹ', 'actions' => ['view', 'print']],
                'price-update' => ['label_en' => 'Price Update', 'label_ur' => 'پرائس اپڈیٹ', 'actions' => ['view', 'edit', 'print']],
            ],
            'sales' => [
                'overview' => ['label_en' => 'Overview', 'label_ur' => 'جائزہ', 'actions' => $viewOnly],
                'pos-sales' => ['label_en' => 'Sales & POS', 'label_ur' => 'سیلز اینڈ پی او ایس', 'actions' => $crudPrint],
                'sales-quotations' => ['label_en' => 'Sales Quotations', 'label_ur' => 'سیلز کوٹیشنز', 'actions' => $crudPrint],
                'online-quotations' => ['label_en' => 'Online Quotations', 'label_ur' => 'آن لائن کوٹیشنز', 'actions' => $crud],
                'order-dispatch' => ['label_en' => 'Order Dispatch', 'label_ur' => 'آرڈر ڈسپیچ', 'actions' => $crudPrint],
                'forwarded-orders' => ['label_en' => 'Forwarded Orders', 'label_ur' => 'فارورڈڈ آرڈرز', 'actions' => $crudPrint],
                'sales-returns' => ['label_en' => 'Sales Returns', 'label_ur' => 'سیلز ریٹرنز', 'actions' => $crudPrint],
                'tasks' => ['label_en' => 'Tasks', 'label_ur' => 'ٹاسکس', 'actions' => $crud],
                'inbox' => ['label_en' => 'Staff Inbox', 'label_ur' => 'اسٹاف ان باکس', 'actions' => $viewEdit],
                'online-orders' => ['label_en' => 'Online Orders', 'label_ur' => 'آن لائن آرڈرز', 'actions' => $crudPrint],
            ],
            'store' => [
                'overview' => ['label_en' => 'Overview', 'label_ur' => 'جائزہ', 'actions' => $viewOnly],
                'messages' => ['label_en' => 'Agricart Inbox', 'label_ur' => 'ایگری کارٹ ان باکس', 'actions' => $viewEdit],
                'reviews' => ['label_en' => 'Reviews', 'label_ur' => 'ریویوز', 'actions' => $viewEdit],
                'brand-reviews' => ['label_en' => 'Brand Reviews', 'label_ur' => 'برانڈ ریویوز', 'actions' => $viewEdit],
                'testimonials' => ['label_en' => 'Testimonials', 'label_ur' => 'تعریفیں', 'actions' => $crud],
                'items' => ['label_en' => 'Store Items', 'label_ur' => 'اسٹور آئٹمز', 'actions' => $crud],
                'visitor-analytics' => ['label_en' => 'Visitor Analytics', 'label_ur' => 'وزیٹر اینالیٹکس', 'actions' => $viewOnly],
                'top-bar' => ['label_en' => 'Top Bar', 'label_ur' => 'ٹاپ بار', 'actions' => $viewEdit],
                'header' => ['label_en' => 'Header', 'label_ur' => 'ہیڈر', 'actions' => $viewEdit],
                'menu' => ['label_en' => 'Menu', 'label_ur' => 'مینو', 'actions' => $viewEdit],
                'ticker' => ['label_en' => 'Ticker', 'label_ur' => 'ٹکر', 'actions' => $viewEdit],
                'footer' => ['label_en' => 'Footer', 'label_ur' => 'فوٹر', 'actions' => $viewEdit],
                'pages' => ['label_en' => 'Pages', 'label_ur' => 'صفحات', 'actions' => $crud],
                'about' => ['label_en' => 'About', 'label_ur' => 'تعارف', 'actions' => $viewEdit],
                'seo' => ['label_en' => 'SEO', 'label_ur' => 'SEO', 'actions' => $viewEdit],
                'registration' => ['label_en' => 'Registration', 'label_ur' => 'رجسٹریشن', 'actions' => $viewEdit],
            ],
            'logistics' => [
                'overview' => ['label_en' => 'Overview', 'label_ur' => 'جائزہ', 'actions' => $viewOnly],
                'delivery-methods' => ['label_en' => 'Delivery Methods', 'label_ur' => 'ڈیلیوری طریقے', 'actions' => $crud],
                'shipments' => ['label_en' => 'Shipments', 'label_ur' => 'شپمنٹس', 'actions' => $crud],
                'shipped' => ['label_en' => 'Shipped', 'label_ur' => 'بھیجے گئے', 'actions' => $viewEdit],
                'delivered' => ['label_en' => 'Delivered', 'label_ur' => 'ڈلیورڈ', 'actions' => $viewEdit],
            ],
            'service' => [
                'overview' => ['label_en' => 'Overview', 'label_ur' => 'جائزہ', 'actions' => $viewOnly],
                'requests' => ['label_en' => 'Requests', 'label_ur' => 'درخواستیں', 'actions' => $crud],
            ],
            'reports' => [
                'overview' => ['label_en' => 'Overview', 'label_ur' => 'جائزہ', 'actions' => $viewOnly],
                'stock-report' => ['label_en' => 'Stock Report', 'label_ur' => 'اسٹاک رپورٹ', 'actions' => $viewPrint],
                'supplier-ledger' => ['label_en' => 'Supplier Ledger', 'label_ur' => 'سپلائر لیجر', 'actions' => $viewPrint],
                'customer-ledger' => ['label_en' => 'Customer Ledger', 'label_ur' => 'گاہک لیجر', 'actions' => $viewPrint],
                'sales-summary' => ['label_en' => 'Sales Summary', 'label_ur' => 'سیلز سمری', 'actions' => $viewPrint],
                'profit-loss' => ['label_en' => 'Profit & Loss', 'label_ur' => 'منافع و نقصان', 'actions' => $viewPrint],
                'expense-report' => ['label_en' => 'Expense Report', 'label_ur' => 'اخراجات رپورٹ', 'actions' => $viewPrint],
                'cash-report' => ['label_en' => 'Cash Report', 'label_ur' => 'کیش رپورٹ', 'actions' => $viewPrint],
                'seasonal-sales' => ['label_en' => 'Seasonal Sales', 'label_ur' => 'موسمی سیلز', 'actions' => $viewPrint],
                'seasonal-purchases' => ['label_en' => 'Seasonal Purchases', 'label_ur' => 'موسمی خریداری', 'actions' => $viewPrint],
            ],
            'staff' => [
                'overview' => ['label_en' => 'Overview', 'label_ur' => 'جائزہ', 'actions' => $viewOnly],
                'staff' => ['label_en' => 'Staff Roster', 'label_ur' => 'عملہ فہرست', 'actions' => $crud],
                'pay-management' => ['label_en' => 'Pay Management', 'label_ur' => 'پے مینجمنٹ', 'actions' => $viewEdit],
                'attendance' => ['label_en' => 'Attendance', 'label_ur' => 'حاضری', 'actions' => $viewEdit],
                'advances' => ['label_en' => 'Advances', 'label_ur' => 'ایڈوانس', 'actions' => $crud],
                'payroll' => ['label_en' => 'Payroll', 'label_ur' => 'پے رول', 'actions' => $viewEdit],
                'leave-management' => ['label_en' => 'Leave Management', 'label_ur' => 'رخصت مینجمنٹ', 'actions' => $viewEdit],
            ],
            'finance' => [
                'overview' => ['label_en' => 'Overview', 'label_ur' => 'جائزہ', 'actions' => $viewOnly],
                'bank-accounts' => ['label_en' => 'Bank Accounts', 'label_ur' => 'بینک اکاؤنٹس', 'actions' => $crudPrint],
                'cash-bank-transfers' => ['label_en' => 'Cash / Bank Transfers', 'label_ur' => 'کیش / بینک ٹرانسفر', 'actions' => $crud],
                'expense-categories' => ['label_en' => 'Expense Categories', 'label_ur' => 'اخراجات کے زمرے', 'actions' => $crud],
                'expenses' => ['label_en' => 'Expenses', 'label_ur' => 'اخراجات', 'actions' => $crud],
                'expense-payments' => ['label_en' => 'Expense Payments', 'label_ur' => 'اخراجات ادائیگیاں', 'actions' => $crud],
                'cash-book' => ['label_en' => 'Cash Book', 'label_ur' => 'کیش بک', 'actions' => $viewPrint],
                'financial-reports' => ['label_en' => 'Financial Reports', 'label_ur' => 'مالی رپورٹس', 'actions' => $viewOnly],
            ],
            'marketplace' => [
                'overview' => ['label_en' => 'Overview', 'label_ur' => 'جائزہ', 'actions' => $viewOnly],
                'sales-channels' => ['label_en' => 'Sales Channels', 'label_ur' => 'سیلز چینلز', 'actions' => $crud],
                'price-control' => ['label_en' => 'Price Control', 'label_ur' => 'پرائس کنٹرول', 'actions' => $viewEdit],
                'quantity-control' => ['label_en' => 'Quantity Control', 'label_ur' => 'کوانٹٹی کنٹرول', 'actions' => $viewEdit],
                'secret-coding' => ['label_en' => 'Secret Coding', 'label_ur' => 'خفیہ کوڈنگ', 'actions' => $viewEdit],
            ],
            'settings' => [
                'overview' => ['label_en' => 'Overview', 'label_ur' => 'جائزہ', 'actions' => $viewOnly],
                'store-profile' => ['label_en' => 'Store Profile', 'label_ur' => 'اسٹور پروفائل', 'actions' => $viewEdit],
                'tax-system' => ['label_en' => 'Tax System', 'label_ur' => 'ٹیکس سسٹم', 'actions' => $viewEdit],
                'brand-watermark' => ['label_en' => 'Brand Watermark', 'label_ur' => 'برانڈ واٹرمارک', 'actions' => $viewEdit],
                'product-watermark' => ['label_en' => 'Product Watermark', 'label_ur' => 'پروڈکٹ واٹرمارک', 'actions' => $viewEdit],
                'brand-review-messages' => ['label_en' => 'Brand Review Messages', 'label_ur' => 'برانڈ ریویو میسجز', 'actions' => $viewEdit],
                'media-library' => ['label_en' => 'Media Library', 'label_ur' => 'میڈیا لائبریری', 'actions' => $crud],
                'printing' => ['label_en' => 'Printing', 'label_ur' => 'پرنٹنگ', 'actions' => $viewEdit],
                'ai-settings' => ['label_en' => 'AI Settings', 'label_ur' => 'اے آئی ترتیبات', 'actions' => $viewEdit],
                'ai-logs' => ['label_en' => 'AI Logs', 'label_ur' => 'اے آئی لاگز', 'actions' => $viewOnly],
                'permission' => ['label_en' => 'Roles & Permissions', 'label_ur' => 'رولز اور اجازتیں', 'actions' => $crud],
                'users' => ['label_en' => 'Users', 'label_ur' => 'یوزرز', 'actions' => $crud],
                'backups' => ['label_en' => 'Backups', 'label_ur' => 'بیک اپ', 'actions' => $crud],
                'system-errors' => ['label_en' => 'System Errors', 'label_ur' => 'سسٹم ایررز', 'actions' => ['view', 'edit', 'delete']],
                'activity' => ['label_en' => 'Activity', 'label_ur' => 'ایکٹیویٹی', 'actions' => $viewOnly],
            ],
            'docs' => [
                'overview' => ['label_en' => 'Overview', 'label_ur' => 'جائزہ', 'actions' => $viewOnly],
                'guide' => ['label_en' => 'Guide', 'label_ur' => 'گائیڈ', 'actions' => $viewOnly],
            ],
        ];
    }

    /**
     * @return array<string, array{label_en: string, label_ur: string, actions: list<string>}>
     */
    public static function pagesFor(string $module): array
    {
        return self::modulePages()[$module] ?? [];
    }

    /**
     * @deprecated Use pagesFor('purchasing')
     *
     * @return array<string, array{label_en: string, label_ur: string, actions: list<string>}>
     */
    public static function purchasingPages(): array
    {
        return self::pagesFor('purchasing');
    }

    /**
     * @return list<string>
     */
    public static function pageSlugs(string $module): array
    {
        return array_keys(self::pagesFor($module));
    }

    /**
     * @deprecated Use pageSlugs('purchasing')
     *
     * @return list<string>
     */
    public static function purchasingPageSlugs(): array
    {
        return self::pageSlugs('purchasing');
    }

    public static function moduleUsesPagePermissions(string $moduleKey): bool
    {
        return isset(self::modulePages()[$moduleKey]);
    }

    /**
     * @return list<string>
     */
    public static function pageScopedModules(): array
    {
        return array_keys(self::modulePages());
    }

    /**
     * @param  array{en: string, ur: string}  $hint
     * @return array{label_en: string, label_ur: string, hint_en: string, hint_ur: string, permissions: list<array{key: string, label_en: string, label_ur: string}>}
     */
    private static function pageScopedGroup(string $module, string $labelEn, string $labelUr, array $hint): array
    {
        $permissions = [];

        foreach (self::pagesFor($module) as $slug => $page) {
            foreach ($page['actions'] as $action) {
                [$actionEn, $actionUr] = self::actionLabels($action);
                $permissions[] = [
                    'key' => ModulePageAccess::key($module, $slug, $action),
                    'label_en' => "{$page['label_en']} — {$actionEn}",
                    'label_ur' => "{$page['label_ur']} — {$actionUr}",
                ];
            }
        }

        return [
            'label_en' => $labelEn,
            'label_ur' => $labelUr,
            'hint_en' => $hint['en'],
            'hint_ur' => $hint['ur'],
            'permissions' => $permissions,
        ];
    }

    /**
     * @return list<array{slug: string, label_en: string, label_ur: string, permissions: list<array{key: string, label_en: string, label_ur: string}>}>
     */
    private static function resourcesForForm(string $module): array
    {
        $resources = [];

        foreach (self::pagesFor($module) as $slug => $page) {
            $permissions = [];

            foreach ($page['actions'] as $action) {
                [$actionEn, $actionUr] = self::actionLabels($action);
                $permissions[] = [
                    'key' => ModulePageAccess::key($module, $slug, $action),
                    'label_en' => $actionEn,
                    'label_ur' => $actionUr,
                ];
            }

            $resources[] = [
                'slug' => $slug,
                'label_en' => $page['label_en'],
                'label_ur' => $page['label_ur'],
                'permissions' => $permissions,
            ];
        }

        return $resources;
    }

    /**
     * @return list<array{key: string, label_en: string, label_ur: string}>
     */
    public static function staffExtraPermissions(): array
    {
        return [
            ['key' => 'staff.view_self', 'label_en' => 'Self-service (my attendance, payroll, leave)', 'label_ur' => 'ذاتی سروس (میری حاضری، تنخواہ، چھٹی)'],
            ['key' => 'staff.attendance.view_all', 'label_en' => 'View all attendance records', 'label_ur' => 'سب کی حاضری دیکھیں'],
            ['key' => 'staff.attendance.mark', 'label_en' => 'Mark daily attendance', 'label_ur' => 'روزانہ حاضری لگائیں'],
            ['key' => 'staff.payroll.view_all', 'label_en' => 'View all payroll records', 'label_ur' => 'سب کی تنخواہ دیکھیں'],
            ['key' => 'staff.leave.view_all', 'label_en' => 'View all leave records', 'label_ur' => 'سب کی چھٹیاں دیکھیں'],
            ['key' => 'staff.advances.view_all', 'label_en' => 'View all advance records', 'label_ur' => 'سب کی ایڈوانس دیکھیں'],
            ['key' => 'staff.advances.manage', 'label_en' => 'Record advances & repayments', 'label_ur' => 'ایڈوانس اور واپسی لکھیں'],
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function actionLabels(string $action): array
    {
        return match ($action) {
            'view' => ['View', 'دیکھیں'],
            'create' => ['Create', 'بنائیں'],
            'edit' => ['Edit', 'ترمیم'],
            'delete' => ['Delete', 'حذف'],
            'print' => ['Print', 'پرنٹ'],
            default => [ucfirst($action), $action],
        };
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

        self::expandLegacyModuleGrants();
        self::expandLegacyFineGrainedGrants();
        self::syncSuperAdminPermissions($ids);

        return $ids;
    }

    /**
     * Keep Super Admin pivots aligned with the registry so Access UI stays truthful.
     *
     * @param  list<int>  $permissionIds
     */
    public static function syncSuperAdminPermissions(array $permissionIds): void
    {
        $role = \App\Models\Role::query()->where('slug', 'super-admin')->first();

        if ($role === null || $permissionIds === []) {
            return;
        }

        $role->permissions()->sync($permissionIds);
        $role->unsetRelation('permissions');
    }

    /**
     * Expand/detach legacy module.view|create|edit|delete for every page-scoped module.
     */
    public static function expandLegacyModuleGrants(): void
    {
        $legacyActions = ['view', 'create', 'edit', 'delete'];

        $roles = \App\Models\Role::query()
            ->where('slug', '!=', 'super-admin')
            ->with('permissions:id,key')
            ->get();

        foreach ($roles as $role) {
            $keys = $role->permissions->pluck('key')->all();
            $changed = false;
            $toAdd = [];
            $toDetach = [];

            foreach (self::pageScopedModules() as $module) {
                $legacyKeys = self::legacyModuleKeys($module);
                $hasLegacy = array_intersect($legacyKeys, $keys) !== [];

                if (! $hasLegacy) {
                    continue;
                }

                $hasPageScoped = collect($keys)->contains(
                    static fn (string $key): bool => str_starts_with($key, "{$module}.")
                        && ! in_array($key, $legacyKeys, true)
                        && preg_match('/^'.preg_quote($module, '/').'\.[a-z0-9-]+\.[a-z_]+$/', $key) === 1,
                );

                if (! $hasPageScoped) {
                    $pages = self::pagesFor($module);

                    foreach ($legacyActions as $action) {
                        if (! in_array("{$module}.{$action}", $keys, true)) {
                            continue;
                        }

                        foreach ($pages as $slug => $page) {
                            if (in_array($action, $page['actions'], true)) {
                                $toAdd[] = ModulePageAccess::key($module, $slug, $action);
                            }

                            if ($action === 'view' && in_array('print', $page['actions'], true)) {
                                $toAdd[] = ModulePageAccess::key($module, $slug, 'print');
                            }
                        }
                    }
                }

                foreach ($legacyKeys as $legacyKey) {
                    $toDetach[] = $legacyKey;
                }

                $changed = true;
            }

            if (! $changed) {
                continue;
            }

            $permissionIds = $toAdd === []
                ? []
                : \App\Models\Permission::query()
                    ->whereIn('key', array_values(array_unique($toAdd)))
                    ->pluck('id')
                    ->all();

            if ($permissionIds !== []) {
                $role->permissions()->syncWithoutDetaching($permissionIds);
            }

            $legacyIds = \App\Models\Permission::query()
                ->whereIn('key', array_values(array_unique($toDetach)))
                ->pluck('id')
                ->all();

            if ($legacyIds !== []) {
                $role->permissions()->detach($legacyIds);
            }

            $role->unsetRelation('permissions');
            $role->touch();
        }
    }

    /**
     * @deprecated Use expandLegacyModuleGrants()
     */
    public static function expandLegacyPurchasingGrants(): void
    {
        self::expandLegacyModuleGrants();
    }

    /**
     * Map old fine-grained settings/store keys onto page-scoped keys, then detach.
     */
    public static function expandLegacyFineGrainedGrants(): void
    {
        $map = self::legacyFineGrainedExpansionMap();

        if ($map === []) {
            return;
        }

        $legacyKeys = array_keys($map);

        $roles = \App\Models\Role::query()
            ->where('slug', '!=', 'super-admin')
            ->with('permissions:id,key')
            ->get();

        foreach ($roles as $role) {
            $keys = $role->permissions->pluck('key')->all();
            $held = array_values(array_intersect($legacyKeys, $keys));

            if ($held === []) {
                continue;
            }

            $toAdd = [];

            foreach ($held as $legacyKey) {
                foreach ($map[$legacyKey] as $pageKey) {
                    $toAdd[] = $pageKey;
                }
            }

            $permissionIds = \App\Models\Permission::query()
                ->whereIn('key', array_values(array_unique($toAdd)))
                ->pluck('id')
                ->all();

            if ($permissionIds !== []) {
                $role->permissions()->syncWithoutDetaching($permissionIds);
            }

            $legacyIds = \App\Models\Permission::query()
                ->whereIn('key', $held)
                ->pluck('id')
                ->all();

            if ($legacyIds !== []) {
                $role->permissions()->detach($legacyIds);
            }

            $role->unsetRelation('permissions');
            $role->touch();
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public static function legacyFineGrainedExpansionMap(): array
    {
        return [
            'store.analytics.view' => [
                ModulePageAccess::key('store', 'visitor-analytics', 'view'),
            ],
            'settings.users.manage' => [
                ModulePageAccess::key('settings', 'users', 'view'),
                ModulePageAccess::key('settings', 'users', 'create'),
                ModulePageAccess::key('settings', 'users', 'edit'),
                ModulePageAccess::key('settings', 'users', 'delete'),
            ],
            'settings.roles.manage' => [
                ModulePageAccess::key('settings', 'permission', 'view'),
                ModulePageAccess::key('settings', 'permission', 'create'),
                ModulePageAccess::key('settings', 'permission', 'edit'),
                ModulePageAccess::key('settings', 'permission', 'delete'),
            ],
            'settings.backups.manage' => [
                ModulePageAccess::key('settings', 'backups', 'view'),
                ModulePageAccess::key('settings', 'backups', 'create'),
                ModulePageAccess::key('settings', 'backups', 'edit'),
                ModulePageAccess::key('settings', 'backups', 'delete'),
            ],
            'settings.printing.manage' => [
                ModulePageAccess::key('settings', 'printing', 'view'),
                ModulePageAccess::key('settings', 'printing', 'edit'),
            ],
            'settings.ai.manage' => [
                ModulePageAccess::key('settings', 'ai-settings', 'view'),
                ModulePageAccess::key('settings', 'ai-settings', 'edit'),
            ],
            'settings.ai.logs.view' => [
                ModulePageAccess::key('settings', 'ai-logs', 'view'),
            ],
            'settings.activity.view' => [
                ModulePageAccess::key('settings', 'activity', 'view'),
            ],
            'settings.system-errors.view' => [
                ModulePageAccess::key('settings', 'system-errors', 'view'),
            ],
            'settings.system-errors.manage' => [
                ModulePageAccess::key('settings', 'system-errors', 'view'),
                ModulePageAccess::key('settings', 'system-errors', 'edit'),
                ModulePageAccess::key('settings', 'system-errors', 'delete'),
            ],
            'settings.media.view' => [
                ModulePageAccess::key('settings', 'media-library', 'view'),
            ],
            'settings.media.manage' => [
                ModulePageAccess::key('settings', 'media-library', 'view'),
                ModulePageAccess::key('settings', 'media-library', 'create'),
                ModulePageAccess::key('settings', 'media-library', 'edit'),
                ModulePageAccess::key('settings', 'media-library', 'delete'),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function legacyModuleKeys(string $module): array
    {
        return [
            "{$module}.view",
            "{$module}.create",
            "{$module}.edit",
            "{$module}.delete",
        ];
    }

    /**
     * @return list<string>
     */
    public static function legacyPurchasingKeys(): array
    {
        return self::legacyModuleKeys('purchasing');
    }

    /**
     * @return list<string>
     */
    public static function allLegacyKeysToStrip(): array
    {
        $keys = [];

        foreach (self::pageScopedModules() as $module) {
            foreach (self::legacyModuleKeys($module) as $key) {
                $keys[] = $key;
            }
        }

        foreach (array_keys(self::legacyFineGrainedExpansionMap()) as $key) {
            $keys[] = $key;
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    public static function withoutLegacyModuleKeys(array $keys): array
    {
        $legacy = self::allLegacyKeysToStrip();

        return array_values(array_filter(
            $keys,
            static fn (string $key): bool => ! in_array($key, $legacy, true),
        ));
    }

    /**
     * @deprecated Use withoutLegacyModuleKeys()
     *
     * @param  list<string>  $keys
     * @return list<string>
     */
    public static function withoutLegacyPurchasingKeys(array $keys): array
    {
        return self::withoutLegacyModuleKeys($keys);
    }
}
