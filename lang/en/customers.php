<?php

return [
    'actions' => [
        'add' => 'Add Customer',
        'edit_short' => 'Edit',
        'view_short' => 'View',
        'delete_short' => 'Delete',
        'delete_confirm' => 'Delete this customer?',
        'remove' => 'Remove',
        'add_phone' => 'Add phone',
        'export_archive' => 'Export archive',
        'export_all' => 'Export all',
        'export_online' => 'Export online',
        'export_offline' => 'Export offline',
        'import_archive' => 'Import archive',
    ],

    'portable' => [
        'hint' => 'Encrypted .ags package. Filename includes customers + optional online/offline + today’s date. Passwords are never exported; online imports get a random password and need a reset before login.',
        'export_ok' => 'Exported :count customers (:scope).',
        'import_ok' => 'Import finished: :created created, :updated updated, :skipped skipped, :media media files.',
        'forbidden' => 'You do not have permission to import or export customers.',
        'scope_all' => 'all',
        'scope_online' => 'online',
        'scope_offline' => 'offline',
    ],

    'sections' => [
        'online_account' => 'Online Registration',
        'account' => 'Account & Limits',
        'documents' => 'Photo',
        'bilingual' => 'Searchable Names & Addresses',
    ],

    'channels' => [
        'online' => 'Online',
        'offline' => 'Offline / Retail',
    ],

    'fields' => [
        'customer_number' => 'Customer No.',
        'display_name' => 'Name',
        'name_en' => 'Name (English)',
        'name_ur' => 'Name (Urdu)',
        'phone' => 'Phone',
        'address_quick' => 'Address',
        'address_en' => 'Address (English)',
        'address_ur' => 'Address (Urdu)',
        'city' => 'City',
        'city_ur' => 'City (Urdu)',
        'opening_balance' => 'Opening Balance',
        'credit_limit' => 'Credit Limit',
        'email' => 'Email',
        'profile_photo' => 'Customer Photo',
        'accepts_marketing' => 'Send promotions by email',
        'is_active' => 'Status',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'address_line1' => 'Address Line 1',
        'address_line2' => 'Address Line 2',
        'state' => 'Province / State',
        'postal_code' => 'Postal Code',
        'country' => 'Country',
    ],

    'hints' => [
        'auto_on_save' => 'Auto generated on save',
        'select_option' => 'Select an option',
        'display_name' => 'English or Urdu — quick counter entry',
        'phone' => '03xx xxxxxxx',
        'address_quick' => 'Optional short address',
        'email_optional' => 'Optional — for promotions',
        'bilingual_ai' => 'Save quickly with name and phone only. Run AI later to fill English/Urdu names and searchable addresses.',
        'opening_balance' => 'Starting ledger amount only — not the live balance. Current balance comes from Customer Ledger (opening + sales − receipts).',
    ],

    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'filters' => [
        'online' => 'Online customers',
        'offline' => 'Offline / retail',
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'list' => [
        'empty' => 'No customers yet. Add a retail customer or wait for online registrations.',
        'col_number' => 'Customer No.',
        'col_name' => 'Name',
        'col_phone' => 'Phone',
        'col_channel' => 'Type',
        'col_city' => 'City',
        'col_balance' => 'Opening Balance',
        'col_created' => 'Added',
        'col_actions' => 'Actions',
    ],

    'messages' => [
        'created' => 'Customer :name saved.',
        'updated' => 'Customer :name updated.',
        'save_failed' => 'Could not save customer. Please check the form and try again.',
        'delete_blocked' => 'This customer has sales, online orders, returns, or fund receipts. Deactivate instead of deleting.',
    ],

    'validation' => [
        'phone_required' => 'Enter at least one phone number.',
        'phone_duplicate' => 'This phone number is already saved for customer “:name”.',
        'phone_duplicate_same_form' => 'The same phone number was entered more than once.',
    ],

    'ai' => [
        'no_configuration' => 'AI is not configured. Set up AI in Settings first.',
        'input_required' => 'Enter a name or address before running AI.',
        'invalid_json' => 'AI returned an invalid response. Please try again.',
        'enriched' => 'English and Urdu fields updated.',
        'failed' => 'AI enrichment failed. Please try again.',
        'prompt' => "You are helping Agricart (agriculture retail in Pakistan) structure customer records for search.\n\nCustomer data:\n:payload",
    ],
];
