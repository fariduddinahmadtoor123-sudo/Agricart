<?php

return [
    'actions' => [
        'add' => 'Add Price Control',
        'edit' => 'Edit :name',
        'edit_short' => 'Edit',
        'delete_short' => 'Delete',
    ],

    'fields' => [
        'number' => 'Control No.',
        'name_en' => 'Name (English)',
        'name_ur' => 'Name (Urdu)',
        'status' => 'Status',
        'notes' => 'Notes',
    ],

    'hints' => [
        'scaffold' => 'This is a starter form. Detailed price control rules will be added in the next step.',
    ],

    'list' => [
        'empty' => 'No price controls yet. Use Add to create the first record.',
        'col_number' => 'No.',
        'col_name_en' => 'Name (EN)',
        'col_name_ur' => 'Name (UR)',
        'col_status' => 'Status',
        'col_created' => 'Created',
        'col_actions' => 'Actions',
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
        'created' => 'Price control saved.',
        'updated' => 'Price control updated.',
        'save_failed' => 'Could not save price control.',
        'delete_confirm' => 'Delete price control ":name"?',
    ],
];
