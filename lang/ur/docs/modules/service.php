<?php

return [
    'overview' => [
        'title' => 'Service Center — جائزہ',
        'summary' => 'جلد آرہا ہے — سروس ٹکٹ لائیو نہیں۔',
        'meta' => [
            'routes' => ['/admin/service/overview'],
            'permissions' => ['service.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'modules_coming_soon؛ BasePlaceholderPage۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'ٹکٹ ماڈل/Livewire ابھی نہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Service\\OverviewPage',
                ],
            ],
        ],
    ],

    'requests' => [
        'title' => 'Service Requests',
        'summary' => 'Placeholder؛ overview ری ڈائریکٹ۔',
        'meta' => [
            'routes' => ['/admin/service/requests'],
            'permissions' => ['service.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'coming soon پر چھپا اور overview پر بھیجے۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'حیثیت/SLA پہلے طے کریں۔',
                ],
            ],
        ],
    ],
];
