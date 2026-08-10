<?php

/**
 * Catalog — Urdu Overview خالی؛ AI Prompt انگریزی؛ Notes خالی۔
 */
return [
    'overview' => [
        'page' => [
            'title' => 'Catalog — Overview',
            'overview' => [],
            'prompt' => <<<'PROMPT'
Build Agricart Catalog Overview: cards/links to Categories, Products, Brands (and related masters). No heavy dashboard widgets.
PROMPT,
            'notes' => [],
        ],
    ],

    'categories' => [
        'list' => [
            'title' => 'Categories — List',
            'overview' => [],
            'prompt' => <<<'PROMPT'
Build Catalog Categories list: flat table, search, Add Category → form panel. Do not build a tree UI yet. Columns: name, slug, status, visual map code.
PROMPT,
            'notes' => [],
        ],
        'form' => [
            'title' => 'Categories — Add / Edit form',
            'overview' => [],
            'prompt' => <<<'PROMPT'
Build Agricart Catalog Add Category form to match live behaviour:

- name_en + name_ur required
- Image required on create; WebP optimized storage; random file name (not numeric)
- Optional parent; ancestor preview slots
- Slug uniquified from name_en
- Visual map code R/M/S/C/L hierarchy; regenerate on save
- AI enrichment (Settings AI)
- Footer: AI → Save & Add Next (keep parent) → Save & Close
- Services: CategoryService, CategoryVisualMappingService, CategoryEnrichmentService
PROMPT,
            'notes' => [],
        ],
    ],

    'products' => [
        'list' => [
            'title' => 'Products — List',
            'overview' => [],
            'prompt' => <<<'PROMPT'
Build Products list: search, filter, Add Product. Highlight low stock when applicable. Open shared product form panel for add/edit.
PROMPT,
            'notes' => [],
        ],
        'form' => [
            'title' => 'Products — Add / Edit form',
            'overview' => [],
            'prompt' => <<<'PROMPT'
Build Add Product form: required name_en, name_ur, category_id. Optional brand, prices, unit/pack, images, AI.
Keep a stepped form but put only required fields on the first step. Use Agricart form panel footer.
PROMPT,
            'notes' => [],
        ],
    ],
];
