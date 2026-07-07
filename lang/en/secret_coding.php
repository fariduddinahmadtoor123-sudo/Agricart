<?php

return [
    'actions' => [
        'add' => 'Add Secret Coding',
        'edit' => 'Edit :name',
        'edit_short' => 'Edit',
        'delete_short' => 'Delete',
    ],

    'fields' => [
        'number' => 'Profile No.',
        'name_en' => 'Name (English)',
        'name_ur' => 'Name (Urdu)',
        'status' => 'Status',
        'notes' => 'Notes',
        'digit' => 'Digit :digit',
    ],

    'sections' => [
        'digit_mapping' => 'Digit Mapping (0–9)',
    ],

    'hints' => [
        'scaffold' => 'This is a starter form. Full secret coding logic will be configured in the next step.',
        'digit_mapping' => 'Assign a secret symbol or letter for each digit. Example: 1 → A, 2 → B.',
    ],

    'list' => [
        'empty' => 'No secret coding profiles yet. Use Add to create the first profile.',
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
        'created' => 'Secret coding profile saved.',
        'updated' => 'Secret coding profile updated.',
        'save_failed' => 'Could not save secret coding profile.',
        'delete_confirm' => 'Delete secret coding profile ":name"?',
    ],
];
