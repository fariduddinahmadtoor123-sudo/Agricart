<?php

return [
    'overview' => [
        'title' => 'Logistics — جائزہ',
        'summary' => 'جلد آرہا ہے — shipments لائیو نہیں۔',
        'meta' => [
            'routes' => ['/admin/logistics/overview'],
            'permissions' => ['logistics.view'],
            'related' => ['sales', 'order-dispatch'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'modules_coming_soon؛ BasePlaceholderPage coming-soon UI۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'آن لائن ڈسپیچ آج Sales → Order Dispatch میں ہے۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Logistics\\OverviewPage',
                ],
            ],
        ],
    ],

    'shipments' => [
        'title' => 'Shipments',
        'summary' => 'Placeholder؛ coming soon پر overview ری ڈائریکٹ۔',
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
                    'sub-nav سے چھپا؛ overview پر بھیجے۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'ڈسپیچ/اسٹاک تقاضے واضح ہونے کے بعد وائر کریں۔',
                ],
            ],
        ],
    ],
];
