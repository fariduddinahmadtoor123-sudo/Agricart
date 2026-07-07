<?php

return [
    'actions' => [
        'add' => 'Add Purchase Planning Sheet',
        'add_product' => 'Add',
        'edit_short' => 'Edit',
        'view_short' => 'View',
        'delete_short' => 'Delete',
        'print_short' => 'Print',
        'print' => 'Save & Print',
        'remove' => 'Remove',
        'delete_confirm' => 'Delete this planning sheet?',
    ],

    'fields' => [
        'planning_number' => 'Planning Number',
        'paper_size' => 'Paper Size',
        'locale' => 'Sheet Language',
        'notes' => 'Notes',
        'product_search' => 'Add Product',
    ],

    'locales' => [
        'en' => 'English',
        'ur' => 'Urdu',
    ],

    'hints' => [
        'auto_on_save' => 'Auto generated on save',
        'notes' => 'Optional note for this sheet',
        'product_search' => 'Search by barcode, code, SKU, or product name…',
    ],

    'paper_sizes' => [
        'a4' => 'A4',
        'a5' => 'A5',
        'a3' => 'A3',
    ],

    'filters' => [
        'paper_size' => 'Paper size',
        'locale' => 'Sheet language',
    ],

    'columns' => [
        'sr' => 'Sr',
        'thumb' => 'Thumb',
        'product_name' => 'Product Name',
        'stock' => 'Stock',
        'required_qty' => 'Required Qty',
        'low_stock' => 'Low Stock',
        'purchase_qty' => 'Purchase Qty',
        'landing_price' => 'Landing Price',
        'sale_price' => 'Sale Price',
    ],

    'form' => [
        'empty' => 'Search and add products to build your purchase planning sheet.',
    ],

    'list' => [
        'empty' => 'No purchase planning sheets yet. Click Add to create one.',
        'col_number' => 'Planning No.',
        'col_items' => 'Items',
        'col_paper' => 'Paper',
        'col_locale' => 'Language',
        'col_created_by' => 'Created By',
        'col_created' => 'Created',
        'col_actions' => 'Actions',
    ],

    'messages' => [
        'created' => 'Planning sheet :number saved.',
        'updated' => 'Planning sheet :number updated.',
        'save_failed' => 'Could not save planning sheet. Please check the form and try again.',
    ],

    'validation' => [
        'items_required' => 'Add at least one product to the planning sheet.',
    ],

    'print' => [
        'title' => 'Purchase Planning',
        'heading' => 'Purchase Planning Sheet',
        'print_button' => 'Print',
        'close_button' => 'Close',
    ],
];
