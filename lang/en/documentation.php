<?php

return [
    'locale' => [
        'english' => 'English',
        'urdu' => 'Urdu',
    ],

    'nav' => [
        'modules' => 'Documentation modules',
        'pages' => 'Pages in this module',
    ],

    'badges' => [
        'documented' => 'Full guide available',
        'brief' => 'Simple rebuild brief available',
        'live' => 'Feature live in app — guide expanding',
        'planned' => 'Coming soon in app',
        'coming_soon_module' => 'Module coming soon',
    ],

    'modes' => [
        'brief' => 'Simple brief (copy prompt)',
        'guide' => 'Detailed guide',
    ],

    'brief' => [
        'howto' => 'Three boxes: (1) Current system — essay, UI Flow, Functional Requirements, fields, Validation, Database, API, Permissions, Edge Cases, Business Rules, Non-Functional Requirements, Dependencies; (2) my improvement opinions; (3) Ready-to-Code AI prompt. Urdu tab = Urdu; English tab = English.',
        'article_heading' => 'Box 1 — What the current system is',
        'fields_heading' => 'Fields (live system)',
        'ui_flow_heading' => 'UI Flow',
        'functional_heading' => 'Functional Requirements',
        'validation_heading' => 'Validation',
        'database_heading' => 'Database',
        'api_heading' => 'API / Integration',
        'permissions_heading' => 'Permissions',
        'edge_cases_heading' => 'Edge Cases',
        'business_rules_heading' => 'Business Rules',
        'non_functional_heading' => 'Non-Functional Requirements',
        'dependencies_heading' => 'Dependencies / Related Modules',
        'section_placeholder' => '— To be filled later —',
        'improvements_heading' => 'Box 2 — My recommendations',
        'improvements_hint' => 'Suggestions only. Decide what to keep or drop before you copy the prompt.',
        'technical_heading' => 'Short technical reference',
        'prompt_heading' => 'Box 3 — Ready-to-Code AI prompt (current system)',
        'prompt_hint' => 'This prompt rebuilds the page as it exists today. It does not include new improvements unless you add them.',
        'copy_prompt' => 'Copy prompt',
        'copied' => 'Copied',
        'missing' => 'A simple brief for this page is not written yet.',
        'missing_hint' => 'Switch to Detailed guide, or ask to add a brief for this page (list + form).',
        'kinds' => [
            'list' => 'List page',
            'form' => 'Add / Edit form',
            'page' => 'Page',
        ],
        'suggest_keep' => 'Keep',
        'suggest_add' => 'Add / improve',
        'suggest_remove' => 'Remove',
        'suggest_optional' => 'Optional',
    ],

    'screenshot_placeholder' => 'Screenshot placeholder — add image later',

    'meta' => [
        'routes' => 'Routes',
        'permissions' => 'Permissions',
        'related' => 'Related',
    ],

    'fields' => [
        'name' => 'Field',
        'label' => 'Label',
        'type' => 'Type',
        'required' => 'Required',
        'rules' => 'Rules / notes',
        'yes' => 'Yes',
        'no' => 'No',
    ],

    'fallback' => [
        'implemented_pending' => 'This screen is live in Agricart. A detailed bilingual guide for this page is being added. Use the app toolbar and form panel as usual; check back here soon.',
        'not_implemented' => 'This screen is not available in the application yet. The menu item may show a placeholder until development is complete.',
        'no_ur_translation' => 'Urdu text for this page is not published yet. Switch to English or check back after the next documentation update.',
    ],

    'overview' => [
        'intro' => 'Agricart Docs mirror the main sidebar: each module has the same sub-pages as the live system. Pick a module, then a page, and read the guide in English or Urdu.',
        'structure' => 'Green dots mark pages with a full written guide. Yellow dots mark live features whose guide is still being expanded. Grey dots mark planned or placeholder screens.',
        'open_guide' => 'Open User Guide',
    ],

    'pdf' => [
        'group_label' => 'Download documentation PDF',
        'page' => 'PDF: This page',
        'module' => 'PDF: This module',
        'book' => 'PDF: Full book',
        'page_hint' => 'Download the current page as a PDF in the selected language.',
        'module_hint' => 'Download every page in the current module as one PDF.',
        'book_hint' => 'Download the full Agricart documentation book (all modules).',
        'preparing' => 'Preparing…',
        'book_title' => 'Agricart User Guide',
        'module_subtitle' => ':module — module documentation',
        'generated_at' => 'Generated :date',
        'toc' => 'Table of contents',
        'scope_page' => 'Single page export',
        'scope_module' => 'Module export',
        'scope_book' => 'Full system documentation book',
        'footer' => 'Agricart documentation export — rebuild-grade staff & developer guide. Save this PDF for offline use.',
        'brief_page' => 'PDF: This brief',
        'brief_module' => 'PDF: Module briefs',
        'brief_book' => 'PDF: All briefs',
        'brief_page_hint' => 'Download simple handoff briefs for this page (list + form if written).',
        'brief_module_hint' => 'Download every simple brief in this module as one PDF.',
        'brief_book_hint' => 'Download all simple rebuild briefs across Agricart.',
        'brief_book_title' => 'Agricart Simple Rebuild Briefs',
        'brief_book_subtitle' => 'Handoff document — fields, improvements, AI prompt',
    ],
];
