<?php

return [
    'actions' => [
        'mark_released' => 'Mark released',
        'release_confirm' => 'Mark these goods as taken out and handed to logistics?',
    ],

    'list' => [
        'note' => 'People and suppliers who already received a forward. When they take out the goods, mark released — the order then appears under Logistics → Pending for bilty / tracking.',
        'empty' => 'Nothing forwarded yet.',
        'empty_hint' => 'Forward packing lists or invoices from Online Orders in Queue. They appear here until released to logistics.',
        'no_results' => 'No recipients match your search.',
        'search' => 'Search recipients or order numbers...',
        'recipient_supplier' => 'Supplier',
        'recipient_staff' => 'Staff',
        'orders_count' => ':count order(s)',
        'lines_count' => ':count line(s)',
        'document_invoice' => 'Invoice',
        'document_packing' => 'Packing list',
        'all_items' => 'Order lines',
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
