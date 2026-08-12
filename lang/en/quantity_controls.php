<?php

return [
    'actions' => [
        'add' => 'Add quantity tier',
        'edit' => 'Edit :name',
        'edit_short' => 'Edit',
        'delete_short' => 'Delete',
    ],

    'fields' => [
        'name_en' => 'Tier name (English)',
        'name_ur' => 'Tier name (Urdu)',
        'min_quantity' => 'Minimum quantity',
        'price_type' => 'Price type',
        'status' => 'Status',
        'map_retail' => 'Retail price',
    ],

    'hints' => [
        'form' => 'Set default minimum quantities for each price type. These auto-fill on the product form; you can override per product.',
        'quantity_preview' => 'Default on product form: ≥ :qty units → :price',
    ],

    'list' => [
        'intro' => 'Default quantity breakpoints for retail, wholesale, bulk and distributor prices. Wholesale, bulk and distributor breaks also appear on the store next to the product price.',
        'link_note' => 'These quantities appear automatically on Add Product and on the online store (for example 5+ Rs 90 · 20+ Rs 80). Prices are managed separately (retail on purchase; others via Price Channel %).',
        'empty' => 'No quantity tiers yet. Use Add to create the first tier.',
        'col_name' => 'Tier',
        'col_min_quantity' => 'Min. qty',
        'col_price_type' => 'Price type',
        'col_status' => 'Status',
        'col_actions' => 'Actions',
    ],

    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'filters' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'messages' => [
        'created' => 'Quantity tier saved.',
        'updated' => 'Quantity tier updated.',
        'save_failed' => 'Could not save quantity tier.',
        'delete_confirm' => 'Delete quantity tier ":name"?',
        'quantity_in_use' => 'Another tier already uses this minimum quantity.',
        'cannot_delete_last_profile' => 'At least one quantity profile must remain.',
    ],
];
