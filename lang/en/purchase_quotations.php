<?php

return [
    'actions' => [
        'add' => 'Add Purchase Quotation',
        'add_product' => 'Add',
        'edit_short' => 'Edit',
        'view_short' => 'View',
        'delete_short' => 'Delete',
        'print_short' => 'Print',
        'print' => 'Save & Print',
        'remove' => 'Remove',
        'change_supplier' => 'Change supplier',
        'delete_confirm' => 'Delete this quotation sheet?',
    ],

    'fields' => [
        'quotation_number' => 'Quotation No.',
        'quotation_date' => 'Date',
        'locale' => 'Sheet Language',
        'supplier' => 'Supplier',
        'notes' => 'Notes',
        'product_search' => 'Add Product',
    ],

    'hints' => [
        'auto_on_save' => 'Auto on save',
        'select_supplier' => 'Select supplier — search by code or name…',
        'product_search' => 'Search by barcode, code, SKU, or name…',
        'notes' => 'Optional note for the supplier',
    ],

    'locales' => [
        'en' => 'English',
        'ur' => 'Urdu',
    ],

    'columns' => [
        'sr' => 'Sr',
        'thumb' => 'Thumb',
        'product_name' => 'Product Name',
        'required_qty' => 'Required Qty',
        'previous_price' => 'Previous Price',
        'quoted_rate' => 'Quoted Rate',
    ],

    'form' => [
        'empty' => 'Select a supplier and add products to build the quotation sheet.',
    ],

    'list' => [
        'empty' => 'No purchase quotation sheets yet. Click Add to create one.',
        'col_number' => 'Quotation No.',
        'col_supplier' => 'Supplier',
        'col_items' => 'Items',
        'col_locale' => 'Language',
        'col_created_by' => 'Created By',
        'col_created' => 'Created',
        'col_actions' => 'Actions',
    ],

    'messages' => [
        'created' => 'Quotation :number saved.',
        'updated' => 'Quotation :number updated.',
        'save_failed' => 'Could not save quotation. Please check the form and try again.',
    ],

    'validation' => [
        'items_required' => 'Add at least one product to the quotation sheet.',
        'supplier_required' => 'Please select a supplier before saving.',
    ],

    'print' => [
        'title' => 'Purchase Quotation',
        'heading' => 'Purchase Quotation Sheet',
        'print_button' => 'Print',
        'close_button' => 'Close',
        'buyer' => 'From (Our Store)',
        'supplier' => 'To (Supplier)',
        'rate_hint' => 'Please write quoted rates in the blank column and return this sheet.',
        'receipt' => [
            'required_qty' => 'Req Qty',
            'previous_price' => 'Prev Price',
            'quoted_rate' => 'Quoted Rate',
        ],
        'layout_a4' => 'A4',
        'layout_receipt' => 'Roll / Receipt',
    ],
];
