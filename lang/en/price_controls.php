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
        'form' => 'Set how much markup to add on purchase price for each sales channel. Retail price on a purchase invoice is still entered by hand.',
        'form_retail' => 'Retail markup is used on Purchasing → Price Update (buy price → retail). Purchases still use the sale price field for retail.',
        'retail_field' => 'Retail always maps to the product retail price field.',
        'custom_field' => 'Extra channels are stored in the product JSON field for future marketplace sync.',
        'example_preview' => 'Example: purchase price :base → channel price :price',
    ],

    'list' => [
        'intro' => 'When you save a purchase, enter purchase price and retail price. Other channels use the markup below. Price Update also uses retail markup when set.',
        'retail_note' => 'On purchases, retail is typed by hand. Set a retail markup % for Price Update auto-fill. Other tiers always use markup on buy price.',
        'empty' => 'No channel tiers yet. Use Add to create the first tier.',
        'col_name' => 'Channel',
        'col_markup' => 'Markup %',
        'col_maps_to' => 'Product field',
        'col_example' => 'Example @ :base',
        'col_example_short' => 'Example',
        'col_status' => 'Status',
        'col_actions' => 'Actions',
        'manual_markup' => 'By hand',
        'manual_example' => 'Enter on purchase (or set % for Price Update)',
        'retail_markup_note' => 'Purchases: manual · Price Update: uses this %',
        'retail_example_note' => 'Used by Price Update sheet',
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
        'cannot_edit_retail' => 'Retail channel names and mapping stay fixed. You can edit the retail markup % for Price Update.',
        'cannot_delete_retail' => 'Retail price tier cannot be removed.',
        'retail_field_reserved' => 'Retail price is managed separately on purchase.',
    ],
];
