<?php

return [
    'fields' => [
        'label_size' => 'Label size',
        'name_language' => 'Name language',
        'code_type' => 'Scan code',
        'show_store_name' => 'Store name',
        'show_product_name' => 'Product name',
        'show_cost_price' => 'Cost price',
        'show_sale_price' => 'Sale price',
        'cost_price' => 'Cost',
        'sale_price' => 'Sale',
        'labels_qty' => 'Labels to print',
        'add_product' => 'Add product',
        'add_product_placeholder' => 'Search name, code, SKU, or scan…',
        'invoice_number' => 'Load from purchase invoice',
        'invoice_placeholder' => 'Purchase invoice number…',
    ],

    'locales' => [
        'en' => 'English',
        'ur' => 'Urdu',
        'both' => 'English + Urdu',
    ],

    'code_types' => [
        'barcode' => 'Barcode',
        'qr' => 'QR code',
        'none' => 'No code',
    ],

    'actions' => [
        'scan_add' => 'Scan / Add',
        'load_invoice' => 'Load invoice',
        'print' => 'Print labels',
        'print_one' => 'Print',
        'add_to_queue' => 'Add to queue',
        'remove' => 'Remove',
        'clear_all' => 'Clear all',
    ],

    'panel' => [
        'title' => 'Print price tags',
        'subtitle' => 'Choose options, build your queue, then print.',
        'close' => 'Close',
        'print_now' => 'Print now',
        'section_label' => 'Label options',
        'section_load' => 'Add products',
        'section_queue' => 'Print queue',
    ],

    'toolbar' => [
        'search' => 'Search products…',
    ],

    'filters' => [
        'readiness' => 'Label readiness',
        'ready_to_label' => 'Ready to label',
        'missing_sale_price' => 'Missing sale price',
        'missing_barcode' => 'Missing barcode / SKU',
    ],

    'messages' => [
        'catalog_empty' => 'No products found for price tags.',
        'catalog_no_results' => 'No products match your search or filter.',
        'empty' => 'Add products manually or load a purchase invoice to build price tags.',
        'queue_empty' => 'Queue is empty. Add products or load an invoice.',
        'empty_queue' => 'Add at least one product before printing.',
        'queue_count' => ':count product(s) in queue',
        'product_added' => 'Product added to price tag queue.',
        'product_not_found' => 'No product matched that code or search.',
        'invoice_required' => 'Enter a purchase invoice number.',
        'invoice_not_found' => 'Purchase invoice not found.',
        'invoice_empty' => 'That invoice has no product lines.',
        'invoice_loaded' => ':count product line(s) loaded from invoice.',
    ],

    'print' => [
        'title' => 'Price Tags',
        'print_button' => 'Print',
        'close_button' => 'Close',
    ],
];
