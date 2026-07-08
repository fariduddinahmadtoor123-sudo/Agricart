<?php

return [
    'actions' => [
        'view_short' => 'View',
        'edit_short' => 'Edit',
        'print' => 'Save & Print invoice',
        'print_short' => 'Invoice',
        'packing_slip' => 'Packing list',
        'packing_slip_short' => 'Packing',
    ],

    'fields' => [
        'order_number' => 'Order No.',
        'order_date' => 'Order Date',
        'order_status' => 'Order Status',
        'pos_invoice' => 'POS Invoice',
        'customer_payment_method' => 'Customer selected',
        'customer_notes' => 'Customer notes',
        'admin_notes' => 'Admin notes',
        'subtotal' => 'Subtotal',
        'total_amount' => 'Total',
        'balance_due' => 'Balance due',
    ],

    'sections' => [
        'shipping' => 'Delivery',
        'customer_payment' => 'Payment (customer declared)',
        'admin_payment' => 'Payment verification (admin)',
    ],

    'columns' => [
        'product_name' => 'Product',
        'brand' => 'Brand',
        'quantity' => 'Qty',
        'unit_price' => 'Unit price',
        'line_total' => 'Amount',
    ],

    'order_statuses' => [
        'pending' => 'Awaiting payment',
        'payment_received' => 'Payment received',
        'processing' => 'Processing',
        'converted' => 'Converted to sale',
        'cancelled' => 'Cancelled',
    ],

    'filters' => [
        'order' => 'Order status',
        'active_order_status' => 'Status: :status',
    ],

    'hints' => [
        'admin_notes' => 'Internal notes for staff…',
        'admin_payment' => 'Confirm how payment was received (method, status, and amount). Save when done.',
        'converted_locked' => 'This order is linked to a completed POS sale and cannot be edited here.',
    ],

    'list' => [
        'empty' => 'No online orders yet.',
        'empty_hint' => 'Orders are created when customers check out on your store. For a test order, run: php artisan online-orders:demo',
        'no_results' => 'No orders match your search or filters.',
        'clear_filters' => 'Clear filters',
        'col_number' => 'Order #',
        'col_customer' => 'Customer',
        'col_total' => 'Total',
        'col_order_status' => 'Order status',
        'col_payment_status' => 'Payment (verified)',
        'col_customer_payment' => 'Customer payment',
        'col_order_date' => 'Order date',
        'col_created' => 'Placed at',
        'col_actions' => 'Actions',
    ],

    'messages' => [
        'updated' => 'Order :number updated.',
        'save_failed' => 'Could not save the order.',
        'order_status_required' => 'Select an order status.',
    ],

    'validation' => [
        'items_required' => 'Add at least one product.',
        'converted_locked' => 'This order is already converted to a sale.',
        'amount_paid_exceeds_total' => 'Paid amount cannot exceed the order total.',
    ],

    'print' => [
        'title' => 'Online order',
        'heading' => 'Online Sale Order',
        'ship_to' => 'Ship to',
    ],
];
