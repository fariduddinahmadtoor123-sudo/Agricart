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
        'brief' => 'User guide available',
        'live' => 'Feature live in app — guide expanding',
        'planned' => 'Coming soon in app',
        'coming_soon_module' => 'Module coming soon',
    ],

    'modes' => [
        'brief' => 'User guide',
        'guide' => 'Detailed guide',
    ],

    'brief' => [
        'howto' => 'Three simple sections: (1) Overview — what this page does and how to use it; (2) AI Prompt — copy this to rebuild the page; (3) Notes — future wishes for this page (fill later). Switch English / Urdu for Overview and Notes.',
        'overview_heading' => '1 — Overview',
        'overview_empty' => 'English overview is ready on the English tab. Urdu overview is empty — paste simple Urdu here in the language file when ready.',
        'article_heading' => '1 — Overview',
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
        'improvements_heading' => '3 — Notes',
        'improvements_hint' => 'Write future changes you want on this page.',
        'notes_heading' => '3 — Notes',
        'notes_hint' => 'Future wishes for this page (not hard-locked). Add lines in the docs language file whenever you need something new here.',
        'notes_empty' => 'No notes yet. You can add English or Urdu notes later for planned changes on this page.',
        'technical_heading' => 'Short technical reference',
        'prompt_heading' => '2 — AI Prompt',
        'prompt_hint' => 'Copy this prompt into an AI tool to rebuild this page as it works today.',
        'prompt_empty' => 'AI prompt is not written for this page yet.',
        'copy_prompt' => 'Copy prompt',
        'copied' => 'Copied',
        'missing' => 'A user guide for this page is not written yet.',
        'missing_hint' => 'Ask to add Overview + AI Prompt for this page (list and/or form).',
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
        'intro' => 'Agricart Docs is a simple user guide. Each page has three parts: Overview (what it does and how to use it), AI Prompt (rebuild instructions), and Notes (future wishes you fill later).',
        'structure' => 'Blue dots mark pages with a user guide. Pick a module, then a page, and switch English / Urdu for Overview and Notes. List and form guides can be selected separately.',
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
        'brief_page' => 'PDF: This guide',
        'brief_module' => 'PDF: Module guides',
        'brief_book' => 'PDF: All guides',
        'brief_page_hint' => 'Download the user guide for this page (list + form if written).',
        'brief_module_hint' => 'Download every user guide in this module as one PDF.',
        'brief_book_hint' => 'Download all Agricart user guides as one book.',
        'brief_book_title' => 'Agricart User Guide',
        'brief_book_subtitle' => 'Overview + AI Prompt + Notes',
    ],
];
