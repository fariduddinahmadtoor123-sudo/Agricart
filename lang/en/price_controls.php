<?php

return [
    'actions' => [
        'add' => 'Add channel tier',
        'edit' => 'Edit :name',
        'edit_short' => 'Edit',
        'delete_short' => 'Delete',
    ],

    'fields' => [
        'name_en' => 'Channel name (English)',
        'name_ur' => 'Channel name (Urdu)',
        'markup_percent' => 'Markup on purchase price (%)',
        'product_field' => 'Save generated price to',
        'status' => 'Status',
        'map_retail' => 'Retail price',
        'map_wholesale' => 'Wholesale price',
        'map_super_wholesale' => 'Bulk price',
        'map_distributor' => 'Distributor price',
        'map_custom' => 'Extra channel (:key)',
        'map_custom_slot' => 'Extra channel slot (JSON)',
    ],

    'hints' => [
        'form' => 'Set how much markup to add on purchase price for each sales channel. Retail price is entered manually on purchase.',
        'custom_field' => 'Extra channels are stored in the product JSON field for future marketplace sync.',
        'example_preview' => 'Example: purchase price :base → channel price :price',
    ],

    'list' => [
        'intro' => 'When you save a purchase, enter purchase price and retail price. The system auto-generates channel prices using the markup below.',
        'retail_note' => 'Retail price is entered manually on each purchase (POS). Channel tiers below apply markup on purchase price only.',
        'empty' => 'No channel tiers yet. Use Add to create the first tier.',
        'col_name' => 'Channel',
        'col_markup' => 'Markup %',
        'col_maps_to' => 'Product field',
        'col_example' => 'Example @ :base',
        'col_example_short' => 'Example',
        'col_status' => 'Status',
        'col_actions' => 'Actions',
        'manual_markup' => 'By hand',
        'manual_example' => 'Enter on purchase',
    ],

    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'filters' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'messages' => [
        'created' => 'Channel tier saved.',
        'updated' => 'Channel tier updated.',
        'save_failed' => 'Could not save channel tier.',
        'delete_confirm' => 'Delete channel tier ":name"?',
        'field_in_use' => 'This product field is already mapped to another tier.',
        'cannot_delete_last_profile' => 'At least one price profile must remain.',
        'cannot_edit_retail' => 'Retail price is entered manually on purchase and cannot be changed here.',
        'cannot_delete_retail' => 'Retail price tier cannot be removed.',
        'retail_field_reserved' => 'Retail price is managed separately on purchase.',
    ],
];
