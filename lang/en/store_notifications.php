<?php

return [
    'order_placed' => [
        'subject' => 'Order :number received — :store',
        'heading' => 'Order :number received',
        'intro' => 'Thank you. We have received your order request and will confirm payment before processing.',
        'total' => 'Order total',
        'bank_hint' => 'If you paid by bank transfer, upload your receipt and transaction reference from your account order page.',
        'col_item' => 'Item',
        'col_qty' => 'Qty',
        'view_order' => 'View order',
        'footer' => 'If you did not place this order, please contact us.',
        'sms' => ':store: Order :number received. Total :total. Track in your account.',
    ],
    'order_shipped' => [
        'subject' => 'Order :number shipped — :store',
        'heading' => 'Order :number is on the way',
        'intro' => 'Your order has been handed to the carrier / transport. Use the details below to track it.',
        'col_carrier' => 'Carrier',
        'col_tracking' => 'Tracking ID',
        'col_bilty' => 'Bilty / reference',
        'hint' => 'For courier tracking IDs, check the carrier website. For bilty shipments, call the phone number printed on the bilty.',
        'view_order' => 'View order',
        'footer' => 'Questions? Reply to this email or contact the store.',
        'sms' => ':store: Order :number shipped. Ref :ref. Details in your account.',
    ],
];
