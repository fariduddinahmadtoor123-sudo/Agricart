<?php

return [
    'sections' => [
        'order' => 'Order to ship',
    ],
    'fields' => [
        'carrier' => 'Carrier / company',
        'tracking' => 'Tracking ID',
        'bilty' => 'Bilty / reference number',
        'proof' => 'Proof image / scan',
    ],
    'labels' => [
        'supplier_parcel' => 'Supplier parcel',
        'staff_parcel' => 'Staff parcel',
    ],
    'help' => [
        'proof_rule' => 'For each parcel enter tracking ID, bilty/reference, or upload a proof image (at least one required).',
        'proof_on_file' => 'A proof file is already saved for this parcel.',
    ],
    'messages' => [
        'shipped' => 'Shipment recorded. The customer has been notified when this is the first handoff.',
        'no_parcels' => 'No released parcels are available to ship on this order.',
        'delivered' => 'Order :number marked delivered. The customer can now review and request a return.',
        'deliver_denied' => 'You do not have permission to mark delivered.',
        'deliver_failed' => 'Could not mark this order delivered.',
    ],
    'validation' => [
        'order_not_found' => 'This order is not available for logistics shipping.',
        'no_parcels' => 'Add at least one released parcel before submitting.',
        'not_ready_to_deliver' => 'Record shipment (tracking or bilty) before marking delivered.',
    ],
    'actions' => [
        'ship' => 'Record shipment',
        'open' => 'Ship',
        'view' => 'View',
        'deliver' => 'Delivered',
        'deliver_confirm' => 'Mark this order delivered? Reviews and return requests will open for the customer.',
    ],
];
