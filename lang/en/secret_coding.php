<?php

return [
    'actions' => [
        'add' => 'Add Secret Coding Channel',
        'edit' => 'Edit :name',
        'edit_short' => 'Edit',
    ],

    'fields' => [
        'number' => 'Profile No.',
        'name_en' => 'Name (English)',
        'name_ur' => 'Name (Urdu)',
        'status' => 'Status',
        'notes' => 'Notes',
    ],

    'digit_equals' => [
        '0' => 'Zero equals',
        '1' => 'One equals',
        '2' => 'Two equals',
        '3' => 'Three equals',
        '4' => 'Four equals',
        '5' => 'Five equals',
        '6' => 'Six equals',
        '7' => 'Seven equals',
        '8' => 'Eight equals',
        '9' => 'Nine equals',
    ],

    'sections' => [
        'digit_mapping' => 'Digit Mapping (0–9)',
        'preview' => 'Example',
    ],

    'hints' => [
        'digit_mapping' => 'Enter any symbol or letter for each digit. Prices will show using these symbols instead of numbers.',
        'preview' => 'Sample price 1500 encoded with your mapping:',
    ],

    'list' => [
        'empty' => 'No secret coding channels yet. Use Add to create the first channel.',
        'col_number' => 'No.',
        'col_name_en' => 'Name (EN)',
        'col_name_ur' => 'Name (UR)',
        'col_mapping' => 'Digit Mapping',
        'col_status' => 'Status',
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
        'created' => 'Secret coding channel saved.',
        'updated' => 'Secret coding channel updated.',
        'save_failed' => 'Could not save secret coding channel.',
    ],
];
