<?php

return [
    'page_title' => 'Carriers',
    'nav' => 'Carriers',
    'intro' => 'Save courier, goods (adda), and cargo companies that move your shipments. They are neither suppliers nor customers.',

    'fields' => [
        'code' => 'Number',
        'type' => 'Company type',
        'status' => 'Status',
        'credit_limit' => 'Credit limit',
        'opening_balance' => 'Opening balance',
        'business_name_en' => 'Business name (English)',
        'business_name_ur' => 'Business name (Urdu)',
        'country' => 'Country',
        'country_ur' => 'Country (Urdu)',
        'city' => 'City',
        'city_ur' => 'City (Urdu)',
        'address_en' => 'Address (English)',
        'address_ur' => 'Address (Urdu)',
        'destination_business_name_en' => 'Business name (English)',
        'destination_business_name_ur' => 'Business name (Urdu)',
        'destination_address_en' => 'Address (English)',
        'destination_address_ur' => 'Address (Urdu)',
        'branch_number' => 'Branch :number',
        'branch_address_en' => 'Branch address (English)',
        'branch_address_ur' => 'Branch address (Urdu)',
        'route_from' => 'From city',
        'route_to' => 'To city',
        'contact_name_en' => 'Contact person (English)',
        'contact_name_ur' => 'Contact person (Urdu)',
        'mobile_number' => 'Mobile number',
        'bank_name_en' => 'Bank (English)',
        'bank_name_ur' => 'Bank (Urdu)',
        'account_title' => 'Account title',
        'account_title_ur' => 'Account title (Urdu)',
        'profile_photo' => 'Profile picture',
        'card_front' => 'Card / bilty (front)',
        'card_back' => 'Card / bilty (back)',
    ],

    'sections' => [
        'destination' => 'Destination Business Address',
    ],

    'hints' => [
        'auto_on_save' => 'Auto-generated on save',
        'select_option' => 'Select…',
        'city_manual' => 'Type city name',
        'opening_balance' => 'Positive if you owe them, negative if they owe you.',
        'branches' => 'Other branches on one line: address (EN/UR), contact (EN/UR), phone. Use + for another person/phone, and + for a new branch.',
        'destination_branches' => 'Destination branches on one line: address (EN/UR), contact (EN/UR), phone. Use + for another person/phone, and + for a new branch.',
        'routes' => 'Routes this carrier runs (city to city). Add one or more.',
        'primary_contact_required' => 'At least one head-office contact person with a mobile number is required.',
        'bank_search' => 'Search bank…',
        'iban_or_account' => 'IBAN or account number',
        'choose_file' => 'Choose file',
    ],

    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'filters' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'actions' => [
        'add' => 'Add carrier',
        'remove' => 'Remove',
        'add_branch' => 'Add branch',
        'remove_branch' => 'Remove branch',
        'add_route' => 'Add route',
        'add_contact' => 'Add contact',
        'add_bank' => 'Add bank',
        'view_short' => 'View',
        'edit_short' => 'Edit',
        'delete_short' => 'Delete',
        'delete_confirm' => 'Delete this carrier?',
    ],

    'list' => [
        'search' => 'Search carriers',
        'empty' => 'No carriers yet.',
        'col_code' => 'Number',
        'col_business' => 'Business name',
        'col_contact' => 'Contact',
        'col_routes' => 'Routes',
        'col_type' => 'Type',
        'col_status' => 'Status',
        'col_created' => 'Created',
        'col_actions' => 'Actions',
    ],

    'validation' => [
        'business_name_required' => 'Business name is required (English or Urdu).',
        'contact_name_required' => 'Contact person name is required (English or Urdu).',
        'mobile_required' => 'Mobile number is required.',
        'address_required' => 'Address is required (English or Urdu).',
    ],

    'messages' => [
        'created' => ':name has been added.',
        'updated' => ':name has been updated.',
        'save_failed' => 'Could not save. Please check the form and try again.',
        'ai_enriched' => 'AI has filled the missing English/Urdu fields.',
        'ai_failed' => 'AI could not complete the request. Please try again.',
    ],
];
