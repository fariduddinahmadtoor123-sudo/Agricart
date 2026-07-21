<?php

return [
    'actions' => [
        'view_short' => 'View',
        'edit_short' => 'Edit',
        'print' => 'Save & Print invoice',
        'print_short' => 'Invoice',
        'packing_slip' => 'Packing list',
        'packing_slip_short' => 'Packing',
        'forward_short' => 'Forward',
        'send_to_queue' => 'Send to Queue',
        'convert_to_invoice' => 'Convert into Invoice',
        'view_customer_receipt' => 'View customer receipt',
        'verify_payment' => 'Verify payment',
        'reject_payment' => 'Reject',
    ],

    'forward' => [
        'eyebrow' => 'Forward order',
        'close' => 'Close',
        'document_label' => 'Document',
        'document_packing' => 'Packing list',
        'document_invoice' => 'Invoice',
        'suppliers_heading' => 'Suppliers (by product category)',
        'staff_heading' => 'Staff',
        'suppliers_empty' => 'No active suppliers match this order’s product categories. Link suppliers to categories in Contacts.',
        'staff_empty' => 'No active staff members found.',
        'no_phone' => 'No phone number',
        'no_email' => 'No email',
        'open' => 'Open',
    ],

    'fields' => [
        'order_number' => 'Order No.',
        'order_date' => 'Order Date',
        'order_status' => 'Order Status',
        'pos_invoice' => 'POS Invoice',
        'customer_payment_method' => 'Customer selected',
        'customer_payment_reference' => 'Transaction reference',
        'delivery_method' => 'Delivery method',
        'payment_proof_status' => 'Receipt status',
        'customer_bank_receipt' => 'Customer receipt upload',
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
        'converted' => 'Invoice issued',
        'cancelled' => 'Cancelled',
    ],

    'filters' => [
        'order' => 'Order status',
        'queue' => 'Queue status',
        'active_order_status' => 'Status: :status',
        'active_queue_status' => 'Queue: :status',
    ],

    'queue_statuses' => [
        'pending' => 'Ready for queue',
        'queued' => 'In queue',
    ],

    'hints' => [
        'admin_notes' => 'Internal notes for staff…',
        'admin_payment' => 'Confirm how payment was received (method, status, and amount). Save when done.',
        'converted_locked' => 'This order is linked to a completed POS sale and cannot be edited here.',
        'reject_payment_note' => 'Reason for customer (optional)',
        'pending_payment' => 'Pending payment',
    ],

    'payment_proof_statuses' => [
        'none' => 'Not required',
        'awaiting_receipt' => 'Awaiting receipt',
        'submitted' => 'Submitted — pending review',
        'verified' => 'Verified',
        'rejected' => 'Rejected — resubmit',
    ],

    'list' => [
        'empty' => 'No online orders yet.',
        'empty_hint' => 'Orders are created when customers check out on your store. For a test order, run: php artisan online-orders:demo',
        'quotations_note' => 'These are online quotations (checkout before invoice). After you verify payment and Convert into Invoice, they move to Online Sale Orders.',
        'quotations_empty' => 'No online quotations yet.',
        'quotations_empty_hint' => 'Quotations appear when a customer completes checkout on the store.',
        'invoices_note' => 'Paid online invoices. Send to Queue when ready for supplier/staff forwarding and logistics.',
        'invoices_empty' => 'No online invoices yet.',
        'invoices_empty_hint' => 'Convert a quotation into an invoice from Online Quotations after payment is verified.',
        'no_results' => 'No orders match your search or filters.',
        'clear_filters' => 'Clear filters',
        'col_number' => 'Order #',
        'col_customer' => 'Customer',
        'col_total' => 'Total',
        'col_order_status' => 'Order status',
        'col_receipt_status' => 'Customer receipt',
        'col_payment_status' => 'Payment',
        'col_queue_status' => 'Queue',
        'col_customer_payment' => 'Customer payment',
        'col_order_date' => 'Order date',
        'col_created' => 'Placed at',
        'col_actions' => 'Actions',
        'receipt_notified' => 'Payment notified',
        'receipt_verified' => 'Receipt verified',
        'receipt_rejected' => 'Receipt rejected',
        'receipt_waiting' => 'Awaiting receipt',
    ],

    'messages' => [
        'updated' => 'Order :number updated.',
        'converted_to_invoice' => 'Quotation :number converted into invoice.',
        'sent_to_queue' => 'Invoice :number sent to the dispatch queue.',
        'save_failed' => 'Could not save the order.',
        'order_status_required' => 'Select an order status.',
        'proof_not_submitted' => 'No customer receipt is pending review.',
        'proof_verified' => 'Customer payment verified.',
        'proof_rejected' => 'Customer payment proof rejected.',
    ],

    'validation' => [
        'items_required' => 'Add at least one product.',
        'converted_locked' => 'This order is already converted to a sale.',
        'fulfillment_requires_invoice' => 'Fulfillment can only be updated after Convert into Invoice.',
        'fulfillment_requires_queue' => 'Fulfillment can only be updated after Send to Queue.',
        'queue_requires_invoice' => 'Only converted invoices can be sent to the dispatch queue.',
        'amount_paid_exceeds_total' => 'Paid amount cannot exceed the order total.',
    ],

    'print' => [
        'title' => 'Online order',
        'heading' => 'Online Sale Order',
        'ship_to' => 'Ship to',
        'appendix_heading' => 'Order status',
    ],
];
