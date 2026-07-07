<?php

return [
    'tabs' => [
        'reorder' => 'Re-Order List',
        'queue' => 'Order Queue',
    ],

    'filters' => [
        'available' => 'Ready to send',
        'in_queue' => 'Already in queue',
        'repeated' => 'Repeated (not received)',
        'all' => 'All low stock',
        'pending' => 'Pending in queue',
        'stale' => 'Stale (7+ days)',
    ],

    'actions' => [
        'send_to_queue' => 'Send to Queue',
        'print' => 'Print Sheet',
        'print_short' => 'Print',
        'select_all' => 'Select all',
    ],

    'toolbar' => [
        'search' => 'Search products…',
        'order_value' => 'Est. cash (prev. rate)',
        'order_detail' => ':lines item(s) · :qty units',
        'selected_only' => 'selected only',
    ],

    'columns' => [
        'sr' => 'Sr',
        'thumb' => 'Logo',
        'product_name' => 'Product Name',
        'required_qty' => 'Required Qty',
        'previous_price' => 'Previous Price',
        'current_price' => 'Current Price',
        'status' => 'Status',
        'batch' => 'Batch',
        'queued_at' => 'Queued',
        'send_count' => 'Times Sent',
    ],

    'status' => [
        'available' => 'Ready',
        'in_queue' => 'In Queue',
        'repeated' => 'Sent :count× before',
        'pending' => 'Pending',
        'stale' => 'Stale',
    ],

    'messages' => [
        'empty_reorder' => 'No products need re-order right now.',
        'empty_queue' => 'Order queue is empty.',
        'sent_to_queue' => ':count item(s) sent to queue (Batch :number).',
        'nothing_to_send' => 'No new items to send — selected products are already in queue.',
        'select_items' => 'Select at least one product to send to queue.',
    ],

    'print' => [
        'title' => 'Re-Order Sheet',
        'heading' => 'Re-Order / Rate Collection Sheet',
        'print_button' => 'Print',
        'close_button' => 'Close',
        'layout_a4' => 'A4',
        'layout_receipt' => 'Roll / Receipt',
        'rate_hint' => 'Write current rates in the blank column. This sheet is for the purchaser only.',
        'receipt' => [
            'required_qty' => 'Req Qty',
            'previous_price' => 'Prev Price',
            'current_price' => 'Current',
        ],
        'estimated_total' => 'Estimated order value (previous rate)',
        'total_meta' => ':lines product(s) · :qty units total',
    ],
];
