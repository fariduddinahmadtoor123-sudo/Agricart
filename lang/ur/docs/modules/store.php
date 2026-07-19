<?php

return [
    'overview' => [
        'title' => 'Online Store — جائزہ',
        'summary' => 'ہوم پیج category گرڈ اور StoreSetting۔',
        'meta' => [
            'routes' => ['/admin/store/overview'],
            'permissions' => ['store.view', 'store.edit'],
            'related' => ['catalog', 'sales', 'marketplace'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'ہوم گرڈ: enable، کالم 3–6، EN/UR عنوانات۔ StoreLayoutService پبلک سائٹ کو فیڈ کرتا ہے۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'Items صفحہ ابھی placeholder ہے۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'livewire' => 'App\\Livewire\\Store\\OverviewSettings',
                    'services' => 'App\\Services\\Store\\StoreLayoutService',
                ],
            ],
        ],
    ],

    'top-bar' => [
        'title' => 'Top Bar',
        'summary' => 'اوپر کی بار: متن، فون، سوشل۔',
        'meta' => [
            'routes' => ['/admin/store/top-bar'],
            'permissions' => ['store.view', 'store.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'store.top-bar-settings → StoreSetting۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'سیٹنگ سٹرنگز — CMS درخت نہیں۔',
                ],
            ],
        ],
    ],

    'ticker' => [
        'title' => 'Ticker',
        'summary' => 'سکرولنگ میسجز + رفتار۔',
        'meta' => [
            'routes' => ['/admin/store/ticker'],
            'permissions' => ['store.view', 'store.create', 'store.edit', 'store.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'StoreTickerMessage CRUD۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'دونوں زبانوں کی تصدیق کریں۔',
                ],
            ],
        ],
    ],

    'header' => [
        'title' => 'Header',
        'summary' => 'نام، لوگو، سرچ، سوشل، auth/cart، WhatsApp۔',
        'meta' => [
            'routes' => ['/admin/store/header'],
            'permissions' => ['store.view', 'store.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'header-settings → StoreSetting۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'URLs حقیقی راستوں سے میل کھائیں۔',
                ],
            ],
        ],
    ],

    'menu' => [
        'title' => 'Menu',
        'summary' => 'مینو آئٹمز: URL / category / store page۔',
        'meta' => [
            'routes' => ['/admin/store/menu'],
            'permissions' => ['store.view', 'store.create', 'store.edit', 'store.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'StoreMenuItem انتظام۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'Catalog visual_mapping مینو نہیں ہے۔',
                ],
            ],
        ],
    ],

    'pages' => [
        'title' => 'Pages',
        'summary' => 'اسٹور فرنٹ CMS صفحات۔',
        'meta' => [
            'routes' => ['/admin/store/pages'],
            'permissions' => ['store.view', 'store.create', 'store.edit', 'store.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'StorePage CRUD۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'ایڈمن Docs ماڈیول سے الگ۔',
                ],
            ],
        ],
    ],

    'footer' => [
        'title' => 'Footer',
        'summary' => 'فوٹر کاپی، لنکس، نقشہ۔',
        'meta' => [
            'routes' => ['/admin/store/footer'],
            'permissions' => ['store.view', 'store.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'StoreFooterLink + سیٹنگز۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'map embed HTML — احتیاط سے sanitize۔',
                ],
            ],
        ],
    ],

    'items' => [
        'title' => 'Store Items',
        'summary' => 'Placeholder — Livewire نہیں۔',
        'meta' => [
            'routes' => ['/admin/store/items'],
            'permissions' => ['store.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'BasePlaceholderPage۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'مصنوعات Catalog + Marketplace چینلز سے۔',
                ],
            ],
        ],
    ],

    'messages' => [
        'title' => 'کسٹمر پیغامات',
        'summary' => 'اسٹور فرنٹ کے فلوٹنگ پیغام فارم سے موصول رابطہ درخواستوں کا محفوظ ان باکس۔',
        'meta' => [
            'routes' => ['/admin/store/messages', '/contact-message'],
            'permissions' => ['store.view', 'store.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'کسٹمر کا نام، فون، اختیاری ای میل، اصل صفحہ اور پیغام دکھائیں۔ مجاز عملہ پیغام پڑھا ہوا، بند یا دوبارہ کھول سکتا ہے۔',
                ],
            ],
            [
                'id' => 'security',
                'type' => 'rebuild_notes',
                'heading' => 'سکیورٹی اور Rebuild نوٹس',
                'list' => [
                    'پبلک فارم CSRF، rate limit اور honeypot سے محفوظ ہے؛ اصل IP کے بجائے keyed hash محفوظ ہوتا ہے۔',
                    'دیکھنے کے لیے store.view اور حالت بدلنے کے لیے store.edit اجازت لازم ہے۔',
                ],
            ],
        ],
    ],

    'visitor-analytics' => [
        'title' => 'وزیٹر اینالیٹکس',
        'summary' => 'اپنے ڈیٹابیس میں محفوظ رازداری پر مبنی وزیٹرز، پیج ویوز، مقامات، ڈیوائسز اور ٹریفک ذرائع۔',
        'meta' => [
            'routes' => ['/admin/store/visitor-analytics', '/analytics/presence'],
            'permissions' => ['store.analytics.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'آج، کل، ہفتہ، مہینہ اور کل وزیٹرز، لائیو موجودگی، مقبول مصنوعات و زمرے، ملک، شہر، ڈیوائس اور ٹریفک ذریعہ ایک ہی ایڈمن صفحے پر دیکھیں۔',
                ],
            ],
            [
                'id' => 'privacy',
                'type' => 'rebuild_notes',
                'heading' => 'رازداری اور مدت',
                'list' => [
                    'اصل IP کبھی محفوظ نہیں ہوتا؛ صرف masked network اور keyed HMAC رکھا جاتا ہے، جبکہ مقامی GeoLite2 سے اندازاً ملک اور شہر معلوم ہوتا ہے۔',
                    'Bots، عملہ، prefetch، DNT/GPC، اندرونی fragments اور غیر HTML درخواستیں شمار نہیں ہوتیں۔',
                    'خام views اور sessions نوے دن بعد حذف ہوتے ہیں جبکہ مختصر روزانہ اور ماہانہ خلاصے محفوظ رہتے ہیں۔',
                ],
            ],
            [
                'id' => 'operations',
                'type' => 'technical',
                'heading' => 'آپریشنز',
                'classes' => [
                    'livewire' => 'App\\Livewire\\Store\\VisitorAnalyticsDashboard',
                    'services' => 'App\\Services\\Analytics\\VisitorAnalyticsTracker; App\\Services\\Analytics\\VisitorAnalyticsReportService',
                ],
            ],
        ],
    ],
];
