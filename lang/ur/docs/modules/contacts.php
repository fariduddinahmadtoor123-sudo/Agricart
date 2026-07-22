<?php

$field = static fn (string $name, string $label, string $type, bool $required, string $rules): array => compact('name', 'label', 'type', 'required', 'rules');

return [
    'overview' => [
        'title' => 'Contacts — جائزہ', 'summary' => 'Customers اور suppliers کے masters جو sales، store، purchasing، finance اور ledgers میں مشترک ہیں۔',
        'meta' => ['routes' => ['/admin/contacts/overview'], 'permissions' => ['contacts.view'], 'related' => ['sales', 'store', 'purchasing', 'finance', 'reports']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'مقصد', 'paragraphs' => ['Contacts party master ہے۔ Customers POS/online خریدار ہیں؛ suppliers vendors، categories، contacts، portal اور payment references رکھتے ہیں۔']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'ریکارڈز', 'fields' => [$field('customers', 'Customers', 'master/auth', true, 'CUS-#####؛ online/offline؛ phone اور credit controls۔'), $field('suppliers', 'Suppliers', 'master/optional portal', true, 'SUP-#####؛ bilingual business، contacts، banks، categories، balances۔')]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'عمل', 'steps' => ['Transaction سے پہلے party تلاش یا create کریں۔', 'Phone، names، address، opening balance اور credit limit verify کریں۔', 'Sales/purchases کو party ID سے جوڑیں۔', 'Customer/Supplier Ledger سے history دیکھیں۔']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI', 'paragraphs' => ['AI bilingual names/address/city تجویز کر سکتا ہے؛ phone، bank number، balance، credit، credentials یا قانونی identity ایجاد نہیں کر سکتا۔']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'قواعد', 'list' => ['RBAC contacts.view/create/edit/delete اور User::hasPermission()۔', 'Phone/email uniqueness identity control ہے۔', 'مالی history والی party کو delete کے بجائے inactive/archive کریں۔']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'روابط', 'list' => ['Sales/POS/Store customers؛ Purchasing/vendor payments suppliers؛ ledger report services؛ supplier-category pivot۔']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild نوٹس', 'list' => ['ایک immutable party ID اور الگ channel authentication۔', 'Financial references پر archive، destructive delete نہیں۔', 'Phones کو canonical indexed table میں منتقل کریں؛ JSON LIKE search نہیں۔']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'قبولیت', 'acceptance' => ['Contact edit کے بعد transaction party ID سے trace ہو۔', 'Canonical duplicate phones reject ہوں۔', 'Unauthorized contacts.* actions UI اور server دونوں پر بند ہوں۔', 'Ledger source transactions اور opening entry سے reconcile ہو۔']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'تکنیکی', 'classes' => ['cluster' => 'App\\Filament\\Clusters\\Contacts', 'models' => ['App\\Models\\Customer', 'App\\Models\\Supplier'], 'services' => ['App\\Services\\Contacts\\CustomerService', 'App\\Services\\Contacts\\SupplierService']]],
        ],
    ],

    'customers' => [
        'title' => 'Customers (گاہک)', 'summary' => 'POS، store login، credit control اور customer ledger کے online/offline خریدار۔',
        'meta' => ['routes' => ['/admin/contacts/customers'], 'permissions' => ['contacts.view', 'contacts.create', 'contacts.edit', 'contacts.delete'], 'related' => ['sales', 'store', 'reports']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'مقصد', 'paragraphs' => ['POS sales اور online orders کے لیے ایک customer identity۔ Offline admin میں بنتا ہے؛ online customer کی authentication-owned fields محفوظ رہتی ہیں۔']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'فیلڈز', 'fields' => [
                $field('customer_number', 'Customer number', 'string', false, 'Offline پر unique auto CUS-#####۔'),
                $field('customer_channel', 'Channel', 'enum', true, 'online|offline؛ admin edit rules بدلتے ہیں۔'),
                $field('display_name / name_en / name_ur', 'نام', 'strings', true, 'Offline display_name required max 255؛ bilingual optional۔'),
                $field('phoneRows / contacts / phone', 'فون', 'JSON + primary', true, 'Offline میں کم از کم ایک؛ normalized duplicate check۔'),
                $field('address_quick / address_en / address_ur', 'پتہ', 'text', false, 'Quick max 500؛ localized max 2000۔'),
                $field('city_key / city_en / city_ur', 'شہر', 'key + strings', false, 'Localized resolution۔'),
                $field('opening_balance', 'Opening balance', 'decimal(2)', false, 'Default 0؛ signed ہو سکتا ہے۔'),
                $field('credit_limit', 'Credit limit', 'decimal(2)', false, 'Nullable، min 0۔'),
                $field('email', 'Email', 'email', false, 'Unique؛ blank offline email internal address بنتی ہے۔'),
                $field('accepts_marketing / is_active', 'Flags', 'boolean', false, 'Consent اور eligibility۔'),
                $field('profile_photo_path', 'Photo', 'WebP path', false, 'Image max 5MB؛ customers/photos۔'),
                $field('ai_status', 'AI status', 'enum', false, 'none|pending|enriched|failed|manual۔'),
            ]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'عمل', 'steps' => ['Name/number/normalized phone سے پہلے تلاش کریں۔', 'Display name + unique phone کے ساتھ offline customer بنائیں۔', 'Address/city/balance/credit/email/photo شامل کریں۔', 'POS/order پر customer ID لگائیں؛ Customer Ledger دیکھیں۔', 'Referenced customer کو inactive کریں، delete نہیں۔']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI enrichment', 'paragraphs' => ['CustomerEnrichmentService bilingual name/address/city مکمل کر سکتی ہے۔ Phone، email، balances، consent، status اور credentials manual/deterministic رہیں۔']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'Validation اور access', 'list' => ['List/Form contacts.{action} permissions چیک کرتے ہیں۔', 'Offline display_name اور ایک phone لازم۔', 'Normalization non-digits/92 prefix سنبھال کر duplicate روکتی ہے۔', 'Online admin edit auth fields نہیں بدلتا۔', 'Sales/orders/returns/fund receipts پر delete بند؛ deactivate کریں۔', '.ags پاس ورڈ ایکسپورٹ نہیں؛ online import random password + reset۔']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'روابط', 'list' => ['POS/Sales customer_id؛ OnlineSaleOrder/auth؛ Customer Ledger (list column = opening only)؛ FundTransfer customer_receipt؛ CustomerPortableArchiveService + AGRICART_PORTABLE_ARCHIVE_KEY؛ photo storage۔']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild نوٹس', 'list' => ['customer_phones child table + normalized unique index (جزوی)۔', 'Opening balance کو immutable ledger entry بنائیں۔', 'Fake offline email کے بجائے nullable/separate credentials۔', 'Archive/anonymize اور credit/status audit۔']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'قبولیت', 'acceptance' => ['Offline create unique CUS-##### + phone۔', '+92/0/spaces ایک canonical identity بنیں۔', 'Online auth fields admin edit سے محفوظ۔', 'Photo replacement کامیاب save کے بعد old file ہٹائے۔', 'Sales/orders/returns/receipts والے customer delete نہیں۔', 'Ledger source activity سے reconcile۔']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'تکنیکی', 'classes' => ['page' => 'App\\Filament\\Clusters\\Contacts\\CustomersPage', 'livewire' => ['App\\Livewire\\Customers\\ListCustomers', 'App\\Livewire\\Customers\\Form'], 'services' => ['App\\Services\\Contacts\\CustomerService', 'App\\Services\\Contacts\\CustomerEnrichmentService', 'App\\Services\\Contacts\\CustomerPortableArchiveService'], 'model' => 'App\\Models\\Customer', 'table' => 'customers']],
        ],
    ],

    'suppliers' => [
        'title' => 'Suppliers (سپلائرز)', 'summary' => 'Bilingual vendor، contacts، bank references، categories، مستقبل کا portal stub، اور payable context۔',
        'meta' => ['routes' => ['/admin/contacts/suppliers'], 'permissions' => ['contacts.view', 'contacts.create', 'contacts.edit', 'contacts.delete'], 'related' => ['purchasing', 'catalog', 'finance', 'reports']],
        'sections' => [
            ['id' => 'purpose', 'type' => 'purpose', 'heading' => 'مقصد', 'paragraphs' => ['Purchases اور supplier-ledger activity کے vendors؛ contacts، categories، bank references، اختیاری مستقبل کی portal credentials اور identity images۔ سپلائر پورٹل لاگ ان ابھی لائیو نہیں۔']],
            ['id' => 'fields', 'type' => 'fields', 'heading' => 'فیلڈز', 'fields' => [
                $field('supplier_code', 'Supplier code', 'string', false, 'Unique auto SUP-#####۔'),
                $field('supplier_type / status', 'قسم/status', 'string / enum', true, 'Type max 64؛ active|inactive۔'),
                $field('business_name_en / business_name_ur', 'Business name', 'strings', true, 'کم از کم ایک؛ max 255۔'),
                $field('country_code / country_ur', 'ملک', 'strings', true, 'Code required max 8۔'),
                $field('city_key / city_en / city_ur', 'شہر', 'key + strings', true, 'Pakistan پر city_key؛ دوسرے ملک پر city_en۔'),
                $field('address_en / address_ur', 'پتہ', 'text', true, 'کم از کم ایک؛ max 2000۔'),
                $field('contacts', 'Contacts', 'JSON rows', true, 'پہلی row میں mobile اور EN/UR contact name۔'),
                $field('bank_accounts', 'Bank references', 'JSON rows', false, 'Bank، bilingual title، account number۔'),
                $field('category_ids', 'Categories', 'pivot IDs', false, 'Existing categories؛ category_supplier sync۔'),
                $field('credit_limit / opening_balance', 'Account controls', 'decimal(2)', false, 'Opening صرف شروع؛ لائیو بیلنس Supplier Ledger۔'),
                $field('portal_enabled / email / password', 'Portal (stub)', 'boolean/credentials', false, 'Credentials محفوظ ہو سکتے ہیں؛ login ابھی لائیو نہیں؛ import portal بند کرتا ہے۔'),
                $field('profile_photo_path / card_front_path / card_back_path', 'Media', 'paths', false, 'Managed public uploads۔'),
                $field('ai_status', 'AI status', 'enum', false, 'none|pending|enriched|failed|manual۔'),
            ]],
            ['id' => 'workflows', 'type' => 'workflows', 'heading' => 'عمل', 'steps' => ['نام/کوڈ/موبائل سے پہلے supplier تلاش کریں۔', 'Business/location/primary contact دیں۔', 'Categories اور optional banks دیں۔', 'Portal عام طور پر بند رکھیں جب تک مستقبل کی credentials نہ چاہییں۔', 'Purchases پر select اور Supplier Ledger میں review۔', 'Referenced supplier inactive کریں۔', 'اختیاری .ags + مشترکہ AGRICART_PORTABLE_ARCHIVE_KEY۔']],
            ['id' => 'ai', 'type' => 'ai', 'heading' => 'AI enrichment', 'paragraphs' => ['SupplierEnrichmentService bilingual business/address/city/contact text تجویز کرتی ہے؛ mobile، bank number، balances، categories، credentials یا portal state نہیں۔']],
            ['id' => 'rules', 'type' => 'rules', 'heading' => 'Validation اور access', 'list' => ['contacts.* permissions۔', 'ایک business name، ایک address، valid primary contact لازم۔', 'Portal create پر email/password لازم؛ login ابھی لائیو نہیں۔', 'Category IDs existing/unique۔', 'Purchases/quotations/payments/assignments پر delete بند۔', '.ags پاس ورڈ نہیں؛ import portal_enabled بند۔']],
            ['id' => 'integrations', 'type' => 'integrations', 'heading' => 'روابط', 'list' => ['Purchases supplier_id؛ category_supplier؛ FundTransfer vendor_payment؛ SupplierLedgerReportService؛ SupplierPortableArchiveService + AGRICART_PORTABLE_ARCHIVE_KEY۔']],
            ['id' => 'rebuild_notes', 'type' => 'rebuild_notes', 'heading' => 'Rebuild نوٹس', 'list' => ['Contacts/banks child tables میں normalize، masking/audit۔', 'Opening balance/vendor payments ledger entries ہوں۔', 'حقیقی supplier portal auth الگ guard۔', 'Delete کے بجائے archive۔']],
            ['id' => 'acceptance', 'type' => 'acceptance', 'heading' => 'قبولیت', 'acceptance' => ['Unique SUP-##### + pivot once۔', 'Primary contact name/mobile لازم۔', 'Referenced supplier destructive delete نہیں۔', 'Vendor payment supplier اور wallet ledgers میں ایک بار۔', 'Opening field شروع کی رقم؛ لائیو بیلنس ledger۔']],
            ['id' => 'technical', 'type' => 'technical', 'heading' => 'تکنیکی', 'classes' => ['page' => 'App\\Filament\\Clusters\\Contacts\\SuppliersPage', 'livewire' => ['App\\Livewire\\Suppliers\\ListSuppliers', 'App\\Livewire\\Suppliers\\Form'], 'services' => ['App\\Services\\Contacts\\SupplierService', 'App\\Services\\Contacts\\SupplierEnrichmentService', 'App\\Services\\Contacts\\SupplierPortableArchiveService'], 'model' => 'App\\Models\\Supplier', 'tables' => ['suppliers', 'category_supplier']]],
        ],
    ],
];
