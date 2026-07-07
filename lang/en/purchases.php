<?php

return [
    'actions' => [
        'add' => 'Add Purchase',
        'add_product' => 'Add',
        'edit_short' => 'Edit',
        'view_short' => 'View',
        'delete_short' => 'Delete',
        'print_short' => 'Print',
        'print' => 'Save & Print',
        'remove' => 'Remove',
        'change_supplier' => 'Change supplier',
        'view_bill' => 'View bill',
        'bill_upload' => 'Upload bill photo',
        'bill_uploading' => 'Uploading…',
        'delete_confirm' => 'Delete this purchase?',
    ],

    'sections' => [
        'buyer' => 'Buyer (Our Store)',
        'supplier' => 'Supplier',
    ],

    'fields' => [
        'purchase_number' => 'Purchase No.',
        'purchase_date' => 'Date',
        'locale' => 'Invoice Language',
        'purchaser' => 'Purchaser',
        'supplier_balance' => 'Supplier Balance',
        'delivery_status' => 'Delivery Status',
        'payment_status' => 'Payment Status',
        'dispute_status' => 'Invoice Status',
        'amount_paid' => 'Amount Paid',
        'subtotal' => 'Subtotal',
        'total_amount' => 'Invoice Total',
        'balance_due' => 'Balance Due',
        'notes' => 'Notes',
        'vendor_bill' => 'Vendor Bill Image',
        'product_search' => 'Add Product',
        'created_by' => 'Created By',
    ],

    'hints' => [
        'auto_on_save' => 'Auto on save',
        'select_purchaser' => 'Select purchaser',
        'select_status' => 'Select…',
        'select_delivery_status' => 'Select delivery status…',
        'select_payment_status' => 'Select payment status…',
        'select_invoice_status' => 'Select invoice status…',
        'select_supplier' => 'Select supplier — search by code or name…',
        'supplier_search' => 'Search supplier by code or name…',
        'product_search' => 'Search by barcode, code, SKU, or name…',
        'notes' => 'Optional note',
        'notes_dispute' => 'Optional note — required detail for disputed invoices',
    ],

    'locales' => [
        'en' => 'English',
        'ur' => 'Urdu',
    ],

    'statuses' => [
        'delivery' => [
            'pending' => 'Goods Pending',
            'partial' => 'Partially Received',
            'received' => 'Goods Received',
        ],
        'payment' => [
            'pending' => 'Payment Pending',
            'partial' => 'Partial Payment',
            'paid' => 'Paid',
        ],
        'dispute' => [
            'ok' => 'Invoice OK',
            'disputed' => 'Disputed',
        ],
    ],

    'filters' => [
        'delivery' => 'Delivery status',
        'payment' => 'Payment status',
        'dispute' => 'Invoice status',
        'active_delivery' => 'Delivery: :status',
        'active_payment' => 'Payment: :status',
        'active_dispute' => 'Invoice: :status',
    ],

    'statuses_short' => [
        'delivery' => [
            'pending' => 'Pending',
            'partial' => 'Partial',
            'received' => 'Received',
        ],
        'payment' => [
            'pending' => 'Pending',
            'partial' => 'Partial',
            'paid' => 'Paid',
        ],
        'dispute' => [
            'ok' => 'OK',
            'disputed' => 'Disputed',
        ],
    ],

    'columns' => [
        'sr' => 'Sr',
        'thumb' => 'Thumb',
        'product_name' => 'Product Name',
        'required_qty' => 'Required Qty',
        'purchase_qty' => 'Purchase Qty',
        'previous_price' => 'Previous Price',
        'current_rate' => 'Current Price',
        'landing_cost' => 'Landing Cost',
        'cost_price' => 'Cost Price',
        'sale_price' => 'Sale Price',
        'line_total' => 'Line Total',
    ],

    'form' => [
        'empty' => 'Select a supplier and add products to build the purchase invoice.',
    ],

    'list' => [
        'empty' => 'No purchases yet. Click Add Purchase to create one.',
        'no_results' => 'No purchases match your search or filters.',
        'clear_filters' => 'Clear filters',
        'col_number' => 'Purchase No.',
        'col_supplier' => 'Supplier',
        'col_purchaser' => 'Purchaser',
        'col_total' => 'Total',
        'col_delivery' => 'Delivery',
        'col_payment' => 'Payment',
        'col_dispute' => 'Dispute',
        'col_created' => 'Created',
        'col_actions' => 'Actions',
    ],

    'messages' => [
        'created' => 'Purchase :number saved.',
        'updated' => 'Purchase :number updated.',
        'save_failed' => 'Could not save purchase. Please check the form and try again.',
        'status_required' => 'Before saving, please select: :fields.',
    ],

    'validation' => [
        'items_required' => 'Add at least one product to the purchase.',
    ],

    'print' => [
        'title' => 'Purchase Invoice',
        'heading' => 'Purchase Invoice',
        'print_button' => 'Print',
        'close_button' => 'Close',
        'layout_a4' => 'A4',
        'layout_receipt' => 'Roll / Receipt',
        'buyer' => 'Buyer',
        'supplier' => 'Supplier',
        'purchaser' => 'Purchaser',
        'supplier_balance' => 'Supplier Previous Balance',
        'receipt' => [
            'required_qty' => 'Req Qty',
            'purchase_qty' => 'Pur Qty',
            'previous_price' => 'Prev Price',
            'current_rate' => 'Current',
            'landing_cost' => 'Landing',
            'cost_price' => 'Cost',
            'sale_price' => 'Sale',
            'line_total' => 'Line Total',
        ],
    ],
];
