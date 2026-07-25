<?php

return [
    'overview' => [
        'title' => 'Dashboard — جائزہ',
        'summary' => 'Placeholder — ویجٹس ابھی نہیں۔',
        'meta' => [
            'routes' => ['/admin/dashboard/overview'],
            'permissions' => ['dashboard.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'BasePlaceholderPage — coming-soon فہرست میں نہیں، مگر مواد نہیں۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'لائیو اعداد ماڈیول overviews/رپورٹس سے لیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Dashboard\\OverviewPage',
                ],
            ],
        ],
    ],

    'summary' => [
        'title' => 'Dashboard Summary',
        'summary' => 'Placeholder خلاصہ صفحہ۔',
        'meta' => [
            'routes' => ['/admin/dashboard/summary'],
            'permissions' => ['dashboard.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'BasePlaceholderPage — نفاذ نہیں۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'موجودہ رپورٹ KPIs سے بنائیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Dashboard\\SummaryPage',
                ],
            ],
        ],
    ],

    'notes' => [
        'title' => 'Dashboard Notes',
        'summary' => 'Placeholder نوٹس صفحہ — BasePlaceholderPage۔',
        'meta' => [
            'routes' => ['/admin/dashboard/notes'],
            'permissions' => ['dashboard.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'آپریٹر نوٹس / شفٹ handoff کے لیے محفوظ — ابھی نفاذ نہیں۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'صرف ملکیت (فی صارف بمقابلہ مشترکہ بورڈ) اور retention واضح ہونے کے بعد شپ کریں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Dashboard\\NotesPage',
                ],
            ],
        ],
    ],
];
