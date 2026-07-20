<?php

return [
    'overview' => [
        'title' => 'Logistics — Overview',
        'summary' => 'Customer-facing delivery methods and shipping policy rules for checkout.',
        'meta' => [
            'routes' => ['/admin/logistics/overview'],
            'permissions' => ['logistics.view'],
            'related' => ['sales', 'order-dispatch', 'store'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Logistics owns admin-configurable Delivery Methods shown on store checkout. Each method has bilingual labels and payment/shipping rules. Sales → Order Dispatch remains the post-sale carrier/tracking workflow.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Do not hardcode delivery method lists in checkout — load active methods via DeliveryMethodService.',
                    'Orders snapshot method labels and rule flags at place-order time so later admin edits do not rewrite history.',
                    'Shipments page stays a placeholder until a true shipment entity exists; operational tracking stays under Sales Order Dispatch.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Logistics\\OverviewPage',
                    'service' => 'App\\Services\\Logistics\\LogisticsOverviewService',
                ],
            ],
        ],
    ],

    'delivery-methods' => [
        'title' => 'Delivery Methods',
        'summary' => 'CRUD for checkout delivery options and 1:1 payment/shipping rules.',
        'meta' => [
            'routes' => ['/admin/logistics/delivery-methods'],
            'permissions' => ['logistics.view', 'logistics.create', 'logistics.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Define Courier, Cargo, Transport Booking, Freight, or any custom method. Rules select allowed payment keys (cash, bank_transfer), COD/advance policy, shipping charge mode, and bilingual checkout notices.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Tables: delivery_methods + delivery_method_rules (1:1).',
                    'Shipping charge mode is policy only in v1 — not a rate calculator.',
                    'COD allowed maps to whether cash may appear with advance rules.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Logistics\\DeliveryMethodsPage',
                    'service' => 'App\\Services\\Logistics\\DeliveryMethodService',
                    'list' => 'App\\Livewire\\DeliveryMethods\\ListMethods',
                    'form' => 'App\\Livewire\\DeliveryMethods\\Form',
                ],
            ],
        ],
    ],

    'shipments' => [
        'title' => 'Shipments',
        'summary' => 'Placeholder until a dedicated shipment entity exists.',
        'meta' => [
            'routes' => ['/admin/logistics/shipments'],
            'permissions' => ['logistics.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Reserved for future shipment records. Use Sales → Order Dispatch for carrier name, tracking, and supplier shipping proof today.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Wire after Sales dispatch and inventory allocation requirements are clear.',
                    'Do not invent courier APIs until Shipments is implemented.',
                ],
            ],
        ],
    ],
];
