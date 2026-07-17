<?php

return [
    'overview' => [
        'title' => 'Logistics — Overview',
        'summary' => 'Coming soon — shipments module not live.',
        'meta' => [
            'routes' => ['/admin/logistics/overview'],
            'permissions' => ['logistics.view'],
            'related' => ['sales', 'order-dispatch'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Planned home for outbound shipments and carrier tracking. Cluster is listed in config agricart.modules_coming_soon; OverviewPage is BasePlaceholderPage with coming-soon UI.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Operational dispatch for online orders lives under Sales → Order Dispatch today — not Logistics.',
                    'Do not invent carrier APIs until Shipments is implemented.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Logistics\\OverviewPage',
                ],
            ],
        ],
    ],

    'shipments' => [
        'title' => 'Shipments',
        'summary' => 'Placeholder; redirects to overview while logistics is coming soon.',
        'meta' => [
            'routes' => ['/admin/logistics/shipments'],
            'permissions' => ['logistics.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'ShipmentsPage extends BasePlaceholderPage. When the cluster is coming soon, mount() redirects to overview and the page is hidden from sub-nav.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Wire after Sales dispatch and inventory allocation requirements are clear.',
                ],
            ],
        ],
    ],
];
