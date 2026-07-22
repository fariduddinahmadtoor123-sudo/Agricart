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
    ],
    'validation' => [
        'order_not_found' => 'This order is not available for logistics shipping.',
        'no_parcels' => 'Add at least one released parcel before submitting.',
    ],
    'actions' => [
        'ship' => 'Record shipment',
        'open' => 'Ship',
        'view' => 'View',
    ],
];
