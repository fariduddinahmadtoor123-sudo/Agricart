<?php

return [
    'overview' => [
        'title' => 'Online Store — Overview',
        'summary' => 'Homepage category grid and storefront chrome settings stored in StoreSetting.',
        'meta' => [
            'routes' => ['/admin/store/overview'],
            'permissions' => ['store.view', 'store.edit'],
            'related' => ['catalog', 'sales', 'marketplace'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Configure homepage category grid: enable, columns (3–6), EN/UR titles. StoreLayoutService aggregates settings for the public site with sales-channel flags.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Items page is still a BasePlaceholderPage — product merchandising for store is not a separate admin list here (catalog + channel flags drive listings).',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'livewire' => 'App\\Livewire\\Store\\OverviewSettings',
                    'services' => 'App\\Services\\Store\\StoreLayoutService',
                ],
            ],
        ],
    ],

    'top-bar' => [
        'title' => 'Top Bar',
        'summary' => 'Enable top bar; left/center/right EN/UR text, phone, social-in-top-bar flags.',
        'meta' => [
            'routes' => ['/admin/store/top-bar'],
            'permissions' => ['store.view', 'store.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Livewire store.top-bar-settings writes StoreSetting keys consumed by the storefront layout.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Content is settings-driven strings — not a CMS page tree.',
                ],
            ],
        ],
    ],

    'ticker' => [
        'title' => 'Ticker',
        'summary' => 'Scrolling ticker enable/speed plus CRUD messages.',
        'meta' => [
            'routes' => ['/admin/store/ticker'],
            'permissions' => ['store.view', 'store.create', 'store.edit', 'store.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'store.ticker-manager: StoreSetting for enable/speed; StoreTickerMessage for message rows.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Messages are bilingual where the form exposes EN/UR fields — verify storefront locale switching.',
                ],
            ],
        ],
    ],

    'header' => [
        'title' => 'Header',
        'summary' => 'Site name, logo, search placeholders, social, auth/cart URLs, WhatsApp.',
        'meta' => [
            'routes' => ['/admin/store/header'],
            'permissions' => ['store.view', 'store.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'store.header-settings persists header chrome to StoreSetting for StoreLayoutService.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Auth/cart URLs are configured strings — ensure they match real storefront routes.',
                ],
            ],
        ],
    ],

    'menu' => [
        'title' => 'Menu',
        'summary' => 'Main nav: enable, home label, items linking to URL / category / store page.',
        'meta' => [
            'routes' => ['/admin/store/menu'],
            'permissions' => ['store.view', 'store.create', 'store.edit', 'store.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'store.menu-manager manages StoreMenuItem rows against Category and StorePage.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'category_display / Hijri visual mapping on catalog categories is separate and not the menu tree.',
                ],
            ],
        ],
    ],

    'pages' => [
        'title' => 'Pages',
        'summary' => 'CMS store pages: slug, content, flags for menu/footer/quick-links/registration.',
        'meta' => [
            'routes' => ['/admin/store/pages'],
            'permissions' => ['store.view', 'store.create', 'store.edit', 'store.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'store.pages-manager CRUD on StorePage for static content and link targets.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Not the Filament admin Docs module — these are public storefront CMS pages.',
                ],
            ],
        ],
    ],

    'footer' => [
        'title' => 'Footer',
        'summary' => 'Footer copy, logos, section titles, map embed, copyright, grouped footer links.',
        'meta' => [
            'routes' => ['/admin/store/footer'],
            'permissions' => ['store.view', 'store.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'store.footer-settings uses StoreSetting, StoreFooterLink, StorePage, Category.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Map embed is raw HTML/embed field — sanitize carefully on rebuild.',
                ],
            ],
        ],
    ],

    'items' => [
        'title' => 'Store Items',
        'summary' => 'Placeholder page — no Livewire manager yet.',
        'meta' => [
            'routes' => ['/admin/store/items'],
            'permissions' => ['store.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Filament ItemsPage extends BasePlaceholderPage (generic placeholder view). Store module is not marked coming-soon.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Sellable products remain Catalog; channel visibility via Marketplace sales channels.',
                    'Implement this page only when a distinct store-item merchandising model exists.',
                ],
            ],
        ],
    ],
];
