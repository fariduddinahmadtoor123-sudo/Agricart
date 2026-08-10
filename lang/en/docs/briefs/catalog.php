<?php

return [
    'overview' => [
        'page' => [
            'title' => 'Catalog — Overview',
            'overview' => [
                'Catalog overview is the map of master product data. Start with Units, Categories, and Brands, then Products.',
                'Use the cards to open each list. Keep this page light — links and short counts only.',
            ],
            'prompt' => <<<'PROMPT'
Build Agricart Catalog Overview: cards/links to Categories, Products, Brands (and related masters). No heavy dashboard widgets.
PROMPT,
            'notes' => [],
        ],
    ],

    'categories' => [
        'list' => [
            'title' => 'Categories — List',
            'overview' => [
                'Categories appear as a flat table on Catalog → Categories. Parent/child is set on the form, not as a tree UI on the list.',
                'Use search, filters, columns, and Add Category to open the form panel. Map code and bilingual names matter for labels and print.',
            ],
            'prompt' => <<<'PROMPT'
Build Catalog Categories list: flat table, search, Add Category → form panel. Do not build a tree UI yet. Columns: name, slug, status, visual map code.
PROMPT,
            'notes' => [],
        ],
        'form' => [
            'title' => 'Categories — Add / Edit form',
            'overview' => [
                'This form creates a product category. On create, an image is required and stored as optimized WebP with a random file name (not a numeric code). Visual Map Code (R1, R1M1, …) is separate and follows hierarchy.',
                'Required: name_en and name_ur. Optional parent category; ancestor image slots preview parents; slot 5 is this category’s own image.',
                'Also includes slug/code/status, content/SEO/FAQ blocks, and AI enrichment from name_en (+ optional image). Footer: AI → Save & Add Next (keeps parent) → Save & Close.',
            ],
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
            'overview' => [
                'Products list is the catalog of sellable items. Use search, filters, and Add Product to open the form.',
                'Low-stock highlighting may appear when stock rules apply.',
            ],
            'prompt' => <<<'PROMPT'
Build Products list: search, filter, Add Product. Highlight low stock when applicable. Open shared product form panel for add/edit.
PROMPT,
            'notes' => [],
        ],
        'form' => [
            'title' => 'Products — Add / Edit form',
            'overview' => [
                'Product form is the central sellable record: bilingual names, primary category required, prices, units/packs, stock, images, attributes.',
                'The form is stepped; put required fields first. AI can fill text content. Use the Agricart form panel footer.',
            ],
            'prompt' => <<<'PROMPT'
Build Add Product form: required name_en, name_ur, category_id. Optional brand, prices, unit/pack, images, AI.
Keep a stepped form but put only required fields on the first step. Use Agricart form panel footer.
PROMPT,
            'notes' => [],
        ],
    ],
];
