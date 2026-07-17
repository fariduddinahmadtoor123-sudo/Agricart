<?php

return [
    'overview' => [
        'title' => 'Service Center — Overview',
        'summary' => 'Coming soon — service tickets not live.',
        'meta' => [
            'routes' => ['/admin/service/overview'],
            'permissions' => ['service.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Planned module for after-sales service requests. Cluster is in modules_coming_soon; OverviewPage is BasePlaceholderPage with coming-soon messaging.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'No ticket model or Livewire queue yet — document only placeholders.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Service\\OverviewPage',
                ],
            ],
        ],
    ],

    'requests' => [
        'title' => 'Service Requests',
        'summary' => 'Placeholder; redirects to overview while service is coming soon.',
        'meta' => [
            'routes' => ['/admin/service/requests'],
            'permissions' => ['service.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'RequestsPage extends BasePlaceholderPage; hidden from sub-nav and redirects to overview when coming soon.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Define request statuses, SLA, and product linkage before building UI.',
                ],
            ],
        ],
    ],
];
