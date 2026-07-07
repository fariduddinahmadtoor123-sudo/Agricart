<?php

return [
    'fields' => [
        'product_search' => 'Search product',
        'product_name' => 'Product',
        'customer' => 'Customer',
        'customer_search' => 'Search customer by name or phone',
        'quantity' => 'Qty',
        'brand' => 'Brand',
        'unit' => 'Unit',
        'unit_price' => 'Price',
        'tier' => 'Rate',
        'line_total' => 'Total',
        'payment_method' => 'Payment method',
        'payment_status' => 'Payment status',
        'amount_paid' => 'Amount received',
        'discount' => 'Discount',
        'discount_type' => 'Discount type',
        'discount_value' => 'Discount value',
        'price_display' => 'Price display',
        'print_controls' => 'Product controls on invoice',
        'bank_account' => 'Bank account',
        'notes' => 'Notes',
        'subtotal' => 'Subtotal',
        'total' => 'Grand total',
        'balance_due' => 'Balance due',
        'sale_date' => 'Date',
        'sale_number' => 'Invoice #',
        'invoice_status' => 'Invoice status',
        'cashier' => 'Cashier',
        'locale' => 'Invoice language',
    ],

    'locales' => [
        'en' => 'English',
        'ur' => 'Urdu',
    ],

    'payment_methods' => [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank transfer',
    ],

    'payment_statuses' => [
        'paid' => 'Paid in full',
        'partial' => 'Partially paid',
        'unpaid' => 'Unpaid',
    ],

    'invoice_statuses' => [
        'held' => 'On hold',
        'completed' => 'Completed',
    ],

    'print_controls_options' => [
        'no' => 'Do not print controls',
        'yes' => 'Print controls on invoice',
    ],

    'discount_types' => [
        '' => 'No discount',
        'percent' => 'Percent %',
        'fixed' => 'Fixed amount',
    ],

    'price_displays' => [
        'plain' => 'Normal numbers',
        'secret' => 'Secret coded prices',
    ],

    'actions' => [
        'add' => 'New sale invoice',
        'clear_customer' => 'Change customer',
        'complete_sale' => 'Complete sale',
        'hold' => 'Hold invoice',
        'add_product' => 'Add',
        'remove' => 'Remove',
        'print' => 'Save & print',
        'view_short' => 'View',
        'edit_short' => 'Resume',
        'print_short' => 'Print',
        'delete_short' => 'Delete',
        'delete_confirm' => 'Delete this sale invoice?',
    ],

    'hints' => [
        'product_search' => 'Name, code, SKU, barcode — pick from list',
        'customer_search' => 'Search customer by name or phone',
        'empty_grid' => 'Search and add products — quantity starts at 0',
        'auto_on_save' => 'Auto on save',
        'select_status' => 'Select…',
        'select_method' => 'Select payment method…',
        'notes' => 'Notes for this invoice',
        'select_bank' => 'Select bank…',
        'no_banks' => 'Bank accounts coming soon',
    ],

    'filters' => [
        'invoice' => 'Invoice status',
        'payment' => 'Payment status',
        'method' => 'Payment method',
        'active_invoice' => 'Invoice: :status',
        'active_payment' => 'Payment: :status',
        'active_method' => 'Method: :method',
    ],

    'list' => [
        'empty' => 'No sale invoices yet. Use Add to open the POS form.',
        'no_results' => 'No invoices match your search or filters.',
        'clear_filters' => 'Clear filters',
        'col_number' => 'Invoice #',
        'col_customer' => 'Customer',
        'col_cashier' => 'Cashier',
        'col_total' => 'Total',
        'col_invoice_status' => 'Invoice',
        'col_payment_status' => 'Payment',
        'col_payment_method' => 'Method',
        'col_sale_date' => 'Date',
        'col_created' => 'Created',
        'col_actions' => 'Actions',
    ],

    'messages' => [
        'completed' => 'Sale :number completed.',
        'updated' => 'Sale :number updated.',
        'held' => 'Sale :number held — stock not changed.',
        'held_updated' => 'Held sale :number updated.',
        'save_failed' => 'Could not save sale. Check the form and try again.',
        'payment_status_required' => 'Select payment status before saving.',
        'payment_method_required' => 'Select payment method (cash or bank) before saving.',
        'bank_account_required' => 'Select which bank account received this payment.',
    ],

    'validation' => [
        'items_required' => 'Enter quantity greater than zero for at least one product.',
        'products_required' => 'Add at least one product to the invoice.',
        'completed_locked' => 'Completed sales cannot be edited.',
        'invalid_status' => 'Invalid invoice status.',
    ],

    'print' => [
        'title' => 'Sales Invoice',
        'heading' => 'Sales Invoice',
        'customer' => 'Customer',
        'cashier' => 'Cashier',
        'print_button' => 'Print',
        'close_button' => 'Close',
        'controls_heading' => 'Product controls',
        'no_controls' => 'No control labels on these products.',
        'layout_a4' => 'A4',
        'layout_receipt' => 'Receipt (3")',
    ],
];
