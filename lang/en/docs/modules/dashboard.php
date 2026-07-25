<?php

return [
    'overview' => [
        'title' => 'Dashboard — Overview',
        'summary' => 'Placeholder — no live widgets yet.',
        'meta' => [
            'routes' => ['/admin/dashboard/overview'],
            'permissions' => ['dashboard.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Intended home for KPIs across sales, stock, and cash. Currently OverviewPage extends BasePlaceholderPage (generic placeholder message).',
                    'Module is not in modules_coming_soon — page simply has no Livewire content yet.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'DocumentationRegistry marks pageIsImplemented false for BasePlaceholderPage.',
                    'Use Purchasing/Sales/Reports overviews for live stats until widgets ship.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Dashboard\\OverviewPage',
                ],
            ],
        ],
    ],

    'summary' => [
        'title' => 'Dashboard Summary',
        'summary' => 'Placeholder summary page — BasePlaceholderPage.',
        'meta' => [
            'routes' => ['/admin/dashboard/summary'],
            'permissions' => ['dashboard.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Second dashboard tab reserved for denser summary tables/charts. Not implemented.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Prefer composing from existing report KPIs rather than inventing new metrics without data sources.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Dashboard\\SummaryPage',
                ],
            ],
        ],
    ],

    'notes' => [
        'title' => 'Dashboard Notes',
        'summary' => 'Placeholder notes page — BasePlaceholderPage.',
        'meta' => [
            'routes' => ['/admin/dashboard/notes'],
            'permissions' => ['dashboard.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Reserved for operator sticky notes / shift handoff. Not implemented yet.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Ship only after clarifying ownership (per-user vs shared board) and retention.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Dashboard\\NotesPage',
                ],
            ],
        ],
    ],
];
