<?php

return [
    'actions' => [
        'add' => 'Add Sale Quotation',
        'add_product' => 'Add',
        'edit_short' => 'Edit',
        'view_short' => 'View',
        'delete_short' => 'Delete',
        'print' => 'Save & Print',
        'print_short' => 'Print',
        'remove' => 'Remove',
        'clear_customer' => 'Clear customer',
        'delete_confirm' => 'Delete this sales quotation?',
    ],

    'fields' => [
        'quotation_number' => 'Quotation No.',
        'quotation_date' => 'Date',
        'quotation_amount' => 'Quotation Total',
        'prepared_by' => 'Prepared By',
        'locale' => 'Sheet Language',
        'notes' => 'Notes',
        'product_search' => 'Add Product',
        'discount_type' => 'Discount Type',
        'discount_value' => 'Discount Value',
        'print_controls' => 'Controls on quotation',
        'subtotal' => 'Subtotal',
        'discount' => 'Discount',
    ],

    'hints' => [
        'auto_on_save' => 'Auto on save',
        'customer_search' => 'Search customer by code or name…',
        'product_search' => 'Search by barcode, code, SKU, or name…',
        'notes' => 'Optional note for the customer',
    ],

    'locales' => [
        'en' => 'English',
        'ur' => 'Urdu',
    ],

    'columns' => [
        'product_name' => 'Product Name',
        'brand' => 'Brand',
        'quantity' => 'Qty',
        'unit' => 'Unit',
        'tier' => 'Tier',
        'unit_price' => 'Unit Price',
        'line_total' => 'Line Total',
    ],

    'list' => [
        'empty' => 'No sales quotations yet. Click Add to create one.',
        'col_number' => 'Quotation No.',
        'col_customer' => 'Customer',
        'col_prepared_by' => 'Prepared By',
        'col_amount' => 'Amount',
        'col_locale' => 'Language',
        'col_quotation_date' => 'Quotation Date',
        'col_created' => 'Created',
        'col_actions' => 'Actions',
    ],

    'messages' => [
        'created' => 'Quotation :number saved.',
        'updated' => 'Quotation :number updated.',
        'save_failed' => 'Could not save quotation. Please check the form and try again.',
    ],

    'validation' => [
        'products_required' => 'Add at least one product to the quotation.',
    ],

    'print' => [
        'title' => 'Sale quotation',
        'heading' => 'Sales Quotation',
        'controls_heading' => 'Product controls (preview)',
    ],
];
