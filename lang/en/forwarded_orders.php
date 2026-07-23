<?php

return [
    'list' => [
        'empty' => 'Nothing forwarded yet.',
        'empty_hint' => 'When you forward an order from Queue to a staff member, it appears here as history.',
        'no_results' => 'No forwards match your search or filters.',
        'search' => 'Search order, invoice, or staff…',
        'clear_filters' => 'Clear filters',
        'release_hint' => 'After packing is done, click Mark released — the order moves to Logistics Pending.',
        'col_order' => 'Order #',
        'col_invoice' => 'Invoice #',
        'col_from' => 'Forwarded by',
        'col_to' => 'Forwarded to',
        'col_status' => 'Status',
        'col_actions' => 'Actions',
        'status_open' => 'Waiting',
        'status_released' => 'Released',
    ],

    'actions' => [
        'mark_released' => 'Mark released',
        'open_packing' => 'Open packing',
        'release_confirm' => 'Mark this forward as released and send it to Logistics Pending?',
    ],

    'filters' => [
        'from' => 'Forwarded by',
        'to' => 'Forwarded to',
        'active_from' => 'From: :name',
        'active_to' => 'To: :name',
    ],

    'validation' => [
        'cannot_release_declined' => 'A declined assignment cannot be released.',
    ],

    'messages' => [
        'released' => 'Marked released — now in Logistics Pending.',
        'release_denied' => 'You do not have permission to mark released.',
        'release_failed' => 'Could not mark released.',
    ],
];
