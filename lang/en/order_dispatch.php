<?php

return [
    'actions' => [
        'open' => 'Open',
        'clear_staff' => 'Clear staff filter',
        'clear_supplier' => 'Clear supplier filter',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'view_proof' => 'View BOL / receipt',
        'packing_slip' => 'Packing list',
    ],

    'sections' => [
        'fulfillment' => 'Fulfillment & assignment',
        'suppliers' => 'Suppliers (by product category)',
    ],

    'fields' => [
        'assigned_staff' => 'Assigned staff',
        'fulfillment_status' => 'Fulfillment status',
        'supplier_status' => 'Supplier status',
        'carrier_name' => 'Courier / carrier',
        'tracking_number' => 'Tracking number',
        'shipping_reference' => 'BOL / receipt ref.',
        'shipping_proof' => 'BOL or receipt (photo/PDF)',
        'supplier_notes' => 'Supplier response notes',
        'acknowledged_at' => 'Acknowledged',
        'shipped_at' => 'Shipped',
    ],

    'filters' => [
        'staff' => 'Staff member',
        'supplier' => 'Supplier',
        'fulfillment' => 'Fulfillment',
        'active_staff' => 'Staff: :name',
        'active_supplier' => 'Supplier: :name',
        'active_fulfillment' => 'Status: :status',
    ],

    'hints' => [
        'all_staff' => 'All staff',
        'all_suppliers' => 'All suppliers',
        'all_fulfillment' => 'All statuses',
        'supplier_manual' => 'Tick suppliers to assign on save. Give staff and suppliers the packing list only (no customer name or prices).',
        'supplier_flow' => 'Assign staff/suppliers, print the packing list for warehouse handoff, then record tracking and BOL/receipt here (customer-facing tracking later).',
        'carrier_name' => 'e.g. Leopards, TCS',
        'shipping_reference' => 'Bilty / receipt number',
        'supplier_notes' => 'What the supplier told you (optional)',
        'no_suppliers' => 'No active suppliers match this order’s product categories. Link suppliers to categories in Contacts.',
    ],

    'validation' => [
        'shipped_proof_required' => 'For Shipped status, enter tracking, BOL/receipt reference, or upload a proof file.',
        'select_items_to_forward' => 'Select at least one product line to forward.',
        'invalid_forward_items' => 'One or more selected lines do not belong to this order.',
        'items_already_forwarded' => 'One or more selected lines were already sent to a supplier.',
        'supplier_required' => 'Choose an active supplier.',
        'packing_for_suppliers' => 'Switch to Packing list to forward lines to suppliers.',
    ],

    'messages' => [
        'packing_partial' => 'Packing progress: :sent of :total lines sent · :remaining remaining — order stays in queue.',
        'packing_complete' => 'All product lines have been sent for packing. Order remains in queue for fulfillment.',
        'supplier_intro' => 'Assalam-o-Alaikum :supplier, please fulfill order :number:',
        'invoice_intro' => 'Assalam-o-Alaikum :name, invoice for order :number:',
        'invoice_total' => 'Total: :amount',
        'invoice_link' => 'Open invoice:',
        'ship_to' => 'Ship to',
        'notes' => 'Notes',
    ],

    'list' => [
        'queue_note' => 'Invoices and packing lists from Ready for Queue. Forward packing lines to suppliers (by category) and invoices to staff. Partial packing keeps the order in queue.',
        'empty' => 'No invoices in the online orders queue.',
        'empty_hint' => 'From Ready for Queue, send Invoice or Packing List to Queue after Convert into Invoice.',
        'no_results' => 'No orders match your search or filters.',
        'col_fulfillment' => 'Fulfillment',
        'col_staff' => 'Staff',
        'col_suppliers' => 'Suppliers',
        'col_packing_progress' => 'Packing',
        'packing_progress' => ':sent / :total lines',
    ],

    'fulfillment_statuses' => [
        'awaiting_assignment' => 'Awaiting assignment',
        'assigned' => 'Assigned to staff',
        'sourcing' => 'Sourcing / supplier',
        'picking_packing' => 'Picking & packing',
        'ready_to_ship' => 'Ready to ship',
        'in_transit' => 'In transit',
        'out_for_delivery' => 'Out for delivery',
        'delivered' => 'Delivered',
        'on_hold' => 'On hold',
    ],

    'supplier_statuses' => [
        'pending' => 'Pending',
        'sent' => 'Sent',
        'acknowledged' => 'Acknowledged',
        'shipped' => 'Shipped',
        'declined' => 'Declined',
    ],

    'email' => [
        'subject' => 'Online order :number',
    ],

    'packing_slip' => [
        'title' => 'Packing list',
        'heading' => 'Packing list — warehouse / supplier',
        'to' => 'To: :name',
        'notice' => 'Pack these items for the order number above. Do not share this slip with the end customer. Customer name, rates, and delivery address are not shown.',
        'footer' => 'Return packed goods to Agricart staff for dispatch. Tracking and bilty details are entered by office staff only.',
        'packed_by' => 'Assigned staff',
        'plain_intro' => 'Please pack order :number (products and quantities only):',
        'plain_footer' => 'Deliver packed order to Agricart. No customer or pricing details on this list.',
    ],
];
