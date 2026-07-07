<?php

return [
    'actions' => [
        'add' => 'Add Sale Return',
        'load_invoice' => 'Load invoice',
        'edit_short' => 'Edit',
        'view_short' => 'View',
        'delete_short' => 'Delete',
        'print' => 'Save & Print',
        'print_short' => 'Print',
        'clear_customer' => 'Clear customer',
        'clear_invoice' => 'Change invoice',
        'delete_confirm' => 'Delete this sale return?',
    ],

    'fields' => [
        'return_number' => 'Return No.',
        'return_date' => 'Return Date',
        'processed_by' => 'Processed By',
        'sale_number' => 'POS Invoice #',
        'customer' => 'Customer',
        'notes' => 'Notes',
        'refund_subtotal' => 'Refund Subtotal',
        'refund_total' => 'Refund Total',
        'balance_due' => 'Balance Due',
    ],

    'hints' => [
        'auto_on_save' => 'Auto on save',
        'customer_search' => 'Search customer by code or name…',
        'sale_number' => 'Enter completed POS invoice number…',
        'notes' => 'Optional internal note',
    ],

    'columns' => [
        'product_name' => 'Product Name',
        'brand' => 'Brand',
        'sold_quantity' => 'Sold Qty',
        'unit_price' => 'Unit Price',
        'return_quantity' => 'Return Qty',
        'return_amount' => 'Return Amount',
    ],

    'list' => [
        'empty' => 'No sale returns yet. Click Add to record a return.',
        'no_results' => 'No sale returns match your search or filters.',
        'clear_filters' => 'Clear filters',
        'col_number' => 'Return No.',
        'col_sale_number' => 'Invoice No.',
        'col_customer' => 'Customer',
        'col_refund_total' => 'Refund Total',
        'col_payment_status' => 'Payment Status',
        'col_payment_method' => 'Payment Method',
        'col_return_date' => 'Return Date',
        'col_created' => 'Created',
        'col_actions' => 'Actions',
    ],

    'messages' => [
        'created' => 'Return :number saved.',
        'updated' => 'Return :number updated.',
        'save_failed' => 'Could not save return. Please check the form and try again.',
        'load_failed' => 'Could not load invoice.',
        'customer_required' => 'Select a customer before loading an invoice.',
        'sale_required' => 'Load a POS invoice with return lines before saving.',
        'invoice_loaded' => 'Invoice :number loaded.',
    ],

    'validation' => [
        'sale_number_required' => 'Enter a POS invoice number.',
        'sale_not_found' => 'No completed sale found with that invoice number.',
        'sale_customer_mismatch' => 'That invoice belongs to a different customer.',
        'items_required' => 'Enter a return quantity for at least one line.',
        'qty_exceeds_sold' => 'Return quantity cannot exceed sold quantity.',
        'qty_exceeds_remaining' => 'Return quantity exceeds remaining returnable quantity.',
    ],

    'print' => [
        'title' => 'Sale return',
        'heading' => 'Sales Return / Credit Note',
    ],
];
