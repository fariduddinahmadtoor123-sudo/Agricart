<?php

return [
    'overview' => [
        'title' => 'Docs — Overview',
        'summary' => 'Two documentation layers for all Agricart modules: simple handoff briefs and detailed developer guides.',
        'meta' => [
            'routes' => ['/admin/docs/overview'],
            'permissions' => ['docs.view'],
            'related' => ['all modules'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Docs Overview is the central entry point for documentation covering all 15 modules. Status dots distinguish simple briefs, detailed guides, live pages, and planned pages.',
                    'Simple briefs serve owners, new staff, and AI handoffs. Detailed guides preserve routes, permissions, fields, services, and code-verified behaviour for developers.',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Use',
                'steps' => [
                    'Open User Guide, select a module, then select a page.',
                    'Read the brief’s explanation, fields, improvements, and ready AI prompt.',
                    'Download the current page, module, or full collection as PDF.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Update both the detailed guide and simple brief in the same change that completes a feature.',
                    'Keep code-verified existing behaviour separate from future recommendations.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Docs\\OverviewPage',
                    'registry' => 'App\\Support\\Documentation\\DocumentationRegistry',
                ],
            ],
        ],
    ],

    'guide' => [
        'title' => 'User Guide and Simple Briefs',
        'summary' => 'One workspace for reading, AI handoff, staff onboarding, and downloadable PDF books.',
        'meta' => [
            'routes' => ['/admin/docs/guide'],
            'permissions' => ['docs.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'User Guide selects the module, page, language, and document mode. A simple brief is the plain-language handoff; a detailed guide is the technical source of truth.',
                    'The ready prompt gives a new-project AI or developer the page purpose, fields, verified behaviour, and proposed improvements in one message.',
                ],
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'Controls',
                'fields' => [
                    ['name' => 'module', 'label' => 'Module', 'type' => 'tabs', 'required' => true, 'rules' => '15 sidebar modules'],
                    ['name' => 'page', 'label' => 'Page', 'type' => 'sidebar', 'required' => true, 'rules' => 'Pages in selected module'],
                    ['name' => 'mode', 'label' => 'Document type', 'type' => 'enum', 'required' => true, 'rules' => 'brief|guide'],
                    ['name' => 'lang', 'label' => 'Language', 'type' => 'enum', 'required' => true, 'rules' => 'ur|en'],
                    ['name' => 'pdf', 'label' => 'PDF scope', 'type' => 'actions', 'required' => false, 'rules' => 'page|module|book'],
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Workflow',
                'steps' => [
                    'Select a page and read its simple brief.',
                    'Use Copy Prompt for a ready handoff message.',
                    'Download the page brief, module briefs, or all briefs PDF.',
                    'Open Detailed guide when implementation-level detail is needed.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Keep generation date and export scope visible in every PDF.',
                    'Smoke-test Urdu font rendering on deployment.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Docs\\GuidePage',
                    'livewire' => 'App\\Livewire\\Docs\\DocumentationShell',
                    'pdf' => 'App\\Services\\Documentation\\DocumentationPdfExporter',
                ],
            ],
        ],
    ],
];
