<?php

return [
    'overview' => [
        'title' => 'Logistics — جائزہ',
        'summary' => 'چیک آؤٹ کے لیے کسٹمر ڈیلیوری طریقے اور شپنگ قواعد۔',
        'meta' => [
            'routes' => ['/admin/logistics/overview'],
            'permissions' => ['logistics.view'],
            'related' => ['sales', 'order-dispatch', 'store'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'Logistics ایڈمن سے ترتیب دیے گئے ڈیلیوری طریقے رکھتا ہے جو اسٹور چیک آؤٹ پر دکھتے ہیں۔ ہر طریقے کے دو زبانوں والے لیبل اور ادائیگی/شپنگ قواعد ہیں۔ سیلز → Order Dispatch بعد از فروخت کیریئر/ٹریکنگ کے لیے ہے۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'چیک آؤٹ میں ڈیلیوری طریقے ہارڈ کوڈ نہ کریں — DeliveryMethodService سے لیں۔',
                    'آرڈر پر لیبل/قواعد کا snapshot محفوظ ہوتا ہے تاکہ بعد میں ایڈمن ترمیم تاریخ نہ بدلے。',
                    'Shipments ابھی placeholder؛ آپریشنل ٹریکنگ Sales Order Dispatch میں ہے۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Logistics\\OverviewPage',
                    'service' => 'App\\Services\\Logistics\\LogisticsOverviewService',
                ],
            ],
        ],
    ],

    'delivery-methods' => [
        'title' => 'ڈیلیوری طریقے',
        'summary' => 'چیک آؤٹ ڈیلیوری اختیارات اور 1:1 ادائیگی/شپنگ قواعد۔',
        'meta' => [
            'routes' => ['/admin/logistics/delivery-methods'],
            'permissions' => ['logistics.view', 'logistics.create', 'logistics.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'کورئیر، کارگو، ٹرانسپورٹ بکنگ، فریٹ یا کوئی بھی طریقہ بنائیں۔ قواعد میں ادائیگی کیز، COD/ایڈوانس، شپنگ چارج موڈ اور دو زبانوں والے نوٹس شامل ہیں۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'ٹیبلز: delivery_methods + delivery_method_rules (1:1)۔',
                    'شپنگ چارج موڈ v1 میں صرف پالیسی ہے — ریٹ کیلکولیٹر نہیں۔',
                    'COD اجازت کا مطلب cash دکھانا اور ایڈوانس قواعد۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Logistics\\DeliveryMethodsPage',
                    'service' => 'App\\Services\\Logistics\\DeliveryMethodService',
                    'list' => 'App\\Livewire\\DeliveryMethods\\ListMethods',
                    'form' => 'App\\Livewire\\DeliveryMethods\\Form',
                ],
            ],
        ],
    ],

    'shipments' => [
        'title' => 'شپمنٹ',
        'summary' => 'الگ شپمنٹ انٹیٹی تک placeholder۔',
        'meta' => [
            'routes' => ['/admin/logistics/shipments'],
            'permissions' => ['logistics.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'آئندہ شپمنٹ ریکارڈز کے لیے محفوظ۔ آج کیریئر نام، ٹریکنگ اور سپلائر شپنگ پروف Sales → Order Dispatch میں ہیں۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'ڈسپیچ اور انوینٹری الاٹمنٹ واضح ہونے کے بعد جوڑیں۔',
                    'Shipments مکمل ہونے تک کورئیر APIs نہ بنائیں۔',
                ],
            ],
        ],
    ],
];
