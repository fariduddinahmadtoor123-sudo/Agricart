<?php

return [
    'actions' => [
        'add' => 'Add Quantity Control',
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
        'scaffold' => 'This is a starter form. Detailed quantity control rules will be added in the next step.',
    ],

    'list' => [
        'empty' => 'No quantity controls yet. Use Add to create the first record.',
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
        'created' => 'Quantity control saved.',
        'updated' => 'Quantity control updated.',
        'save_failed' => 'Could not save quantity control.',
        'delete_confirm' => 'Delete quantity control ":name"?',
    ],
];
