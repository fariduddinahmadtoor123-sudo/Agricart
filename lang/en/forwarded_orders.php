<?php

return [
    'list' => [
        'empty' => 'Nothing forwarded yet.',
        'empty_hint' => 'When you forward an order from Queue to a staff member, it appears here as history.',
        'no_results' => 'No forwards match your search or filters.',
        'search' => 'Search order, invoice, or staff…',
        'clear_filters' => 'Clear filters',
        'col_order' => 'Order #',
        'col_invoice' => 'Invoice #',
        'col_from' => 'Forwarded by',
        'col_to' => 'Forwarded to',
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
