<?php

return [
    'overview' => [
        'title' => 'Channels Hub — جائزہ',
        'summary' => 'Marketplace: چینلز، قیمت/مقدار، secret coding۔',
        'meta' => [
            'routes' => ['/admin/marketplace/overview'],
            'permissions' => ['marketplace.view'],
            'related' => ['catalog', 'purchasing', 'store', 'sales'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'چینل فعالیت اور قیمت/مقدار/کوڈنگ قواعد۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'slug marketplace؛ لیبل Channels Hub۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'livewire' => 'App\\Livewire\\Marketplace\\Overview',
                    'services' => 'App\\Services\\Marketplace\\MarketplaceOverviewService',
                ],
            ],
        ],
    ],

    'sales-channels' => [
        'title' => 'Sales Channels',
        'summary' => 'SalesChannel ریکارڈز۔',
        'meta' => [
            'routes' => ['/admin/marketplace/sales-channels'],
            'permissions' => ['marketplace.view', 'marketplace.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'اسٹور/POS چینل جھنڈے۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'slug: sales-channels (جمع)۔',
                ],
            ],
        ],
    ],

    'price-control' => [
        'title' => 'Price Channel',
        'summary' => 'PriceChannelTier / ChannelPricingEngine۔',
        'meta' => [
            'routes' => ['/admin/marketplace/price-control'],
            'permissions' => ['marketplace.view', 'marketplace.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'خرید سیو پر قیمت اپڈیٹ قواعد۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'خالی tiers → pricing skip۔',
                ],
            ],
        ],
    ],

    'quantity-control' => [
        'title' => 'Quantity Channel',
        'summary' => 'QuantityChannelTier / POS QuantityPricingEngine۔',
        'meta' => [
            'routes' => ['/admin/marketplace/quantity-control'],
            'permissions' => ['marketplace.view', 'marketplace.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'مقدار بنیاد پر قیمت۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'ٹیر فیلڈز Livewire سے تفصیل کریں۔',
                ],
            ],
        ],
    ],

    'secret-coding' => [
        'title' => 'Secret Coding Channel',
        'summary' => 'SecretCodingProfile / SecretCodingEngine۔',
        'meta' => [
            'routes' => ['/admin/marketplace/secret-coding'],
            'permissions' => ['marketplace.view', 'marketplace.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'حساس رقم کی کوڈنگ (reorder، payment summary)۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'UI کوڈنگ — تمام finance encryption نہیں۔',
                ],
            ],
        ],
    ],
];
