<?php

return [
    'overview' => [
        'title' => 'Channels Hub — Overview',
        'summary' => 'Marketplace module (nav: Channels Hub): sales channels, price/quantity tiers, secret coding.',
        'meta' => [
            'routes' => ['/admin/marketplace/overview'],
            'permissions' => ['marketplace.view'],
            'related' => ['catalog', 'purchasing', 'store', 'sales'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Controls which channels are active and how retail/quantity rules and secret amount coding apply on top of catalog products.',
                    'Overview Livewire uses MarketplaceOverviewService / search.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Cluster slug remains marketplace; sidebar label is Channels Hub.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'livewire' => 'App\\Livewire\\Marketplace\\Overview',
                    'services' => 'App\\Services\\Marketplace\\MarketplaceOverviewService',
                ],
            ],
        ],
    ],

    'sales-channels' => [
        'title' => 'Sales Channels',
        'summary' => 'Toggle and configure SalesChannel records (e.g. online store channel flags).',
        'meta' => [
            'routes' => ['/admin/marketplace/sales-channels'],
            'permissions' => ['marketplace.view', 'marketplace.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'ListSalesChannels + SalesChannelService manage channel rows consumed by StoreLayoutService and POS/online pricing paths.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Slug is sales-channels (plural), not sales-channel.',
                ],
            ],
        ],
    ],

    'price-control' => [
        'title' => 'Price Channel',
        'summary' => 'PriceChannelTier / markup rules applied when purchases or POS pricing engines run.',
        'meta' => [
            'routes' => ['/admin/marketplace/price-control'],
            'permissions' => ['marketplace.view', 'marketplace.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Maintain price tier configuration used by ChannelPricingEngine (e.g. after purchase save).',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'If markupTiers() is empty, PurchaseService skips applyChannelPricing.',
                ],
            ],
        ],
    ],

    'quantity-control' => [
        'title' => 'Quantity Channel',
        'summary' => 'QuantityChannelTier for quantity-based pricing (QuantityPricingEngine on POS).',
        'meta' => [
            'routes' => ['/admin/marketplace/quantity-control'],
            'permissions' => ['marketplace.view', 'marketplace.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Configure quantity break tiers consumed when SaleService recalculates POS rows (forPos: true).',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Document exact tier fields from Livewire form when rebuilding quantity rules UI.',
                ],
            ],
        ],
    ],

    'secret-coding' => [
        'title' => 'Secret Coding Channel',
        'summary' => 'SecretCodingProfile for encoding sensitive amounts (e.g. reorder totals, payment summary sources).',
        'meta' => [
            'routes' => ['/admin/marketplace/secret-coding'],
            'permissions' => ['marketplace.view', 'marketplace.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Profiles used by SecretCodingEngine in purchasing reorder UI and payment summary amounts.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Encoding is presentation/privacy for staff screens — not encryption at rest for all finance fields.',
                ],
            ],
        ],
    ],
];
