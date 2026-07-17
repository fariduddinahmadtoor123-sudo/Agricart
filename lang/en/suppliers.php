<?php

return [
    'actions' => [
        'add' => 'Add Supplier',
        'edit_short' => 'Edit',
        'view_short' => 'View',
        'delete_short' => 'Delete',
        'delete_confirm' => 'Delete this supplier?',
        'remove' => 'Remove',
        'add_contact' => 'Add contact',
        'add_bank' => 'Add bank account',
    ],

    'sections' => [
        'business' => 'Business Information',
        'categories' => 'Supply Categories',
        'contacts' => 'Contacts',
        'banks' => 'Bank Accounts',
        'portal' => 'Portal Access',
        'documents' => 'Documents',
    ],

    'fields' => [
        'supplier_code' => 'Supplier Code',
        'supplier_type' => 'Supplier Type',
        'status' => 'Status',
        'country' => 'Country',
        'country_ur' => 'Country (Urdu)',
        'city' => 'City',
        'city_en' => 'City (English)',
        'city_ur' => 'City (Urdu)',
        'address_en' => 'Full Address',
        'address_ur' => 'Address (Urdu)',
        'business_name_en' => 'Business Name',
        'business_name_ur' => 'Business Name (Urdu)',
        'contact_name_en' => 'Contact Name',
        'contact_name_ur' => 'Contact Name (Urdu)',
        'mobile_number' => 'Mobile Number',
        'credit_limit' => 'Credit Limit',
        'opening_balance' => 'Opening Balance',
        'categories' => 'Categories',
        'bank_name' => 'Bank Name',
        'bank_name_en' => 'Bank Name (English)',
        'bank_name_ur' => 'Bank Name (Urdu)',
        'branch_name' => 'Branch Name',
        'account_title' => 'Account Title',
        'account_title_ur' => 'Account Title (Urdu)',
        'account_number' => 'IBAN / Account Number',
        'portal_enabled' => 'Allow supplier portal login',
        'email' => 'Email',
        'password' => 'Password',
        'password_confirm' => 'Confirm Password',
        'profile_photo' => 'Profile Photo',
        'card_front' => 'Card Front Side',
        'card_back' => 'Card Back Side',
    ],

    'hints' => [
        'auto_on_save' => 'Auto generated on save',
        'select_option' => 'Select an option',
        'category_search' => 'Search categories to tag…',
        'no_categories' => 'No matching categories.',
        'bank_search' => 'Search bank by English, Urdu, or code (HBL, UBL, NBP)…',
        'iban_or_account' => 'IBAN or account number',
        'choose_file' => 'No file chosen',
        'portal_later' => 'Optional for now. When enabled, the supplier can sign in later to view ledgers and account details.',
        'address_ur_panel' => 'Urdu address (auto-filled where possible)',
        'city_manual' => 'Enter city name',
        'primary_contact_required' => 'Primary contact name and mobile number are required (marked *).',
    ],

    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'messages' => [
        'created' => 'Supplier :name saved successfully.',
        'updated' => 'Supplier :name updated successfully.',
        'save_failed' => 'Could not save supplier. Please check the form and try again.',
        'delete_blocked' => 'This supplier has purchases, quotations, payments, or order assignments. Set status to inactive instead of deleting.',
    ],

    'validation' => [
        'contact_required' => 'Add at least one contact with a mobile number.',
        'mobile_required' => 'Primary contact mobile number is required.',
        'contact_name_required' => 'Enter the primary contact name in English or Urdu.',
    ],

    'ai' => [
        'no_configuration' => 'No active AI configuration. Add one in Settings → AI.',
        'input_required' => 'Fill at least one English or Urdu field before using AI.',
        'invalid_json' => 'AI returned an invalid response. Please try again.',
        'enriched' => 'Missing English and Urdu fields filled. Review and save.',
        'prompt' => 'You are completing a supplier profile for an agriculture and hardware retailer in Pakistan. Current form data (JSON):\n:payload',
    ],

    'list' => [
        'empty' => 'No suppliers yet. Click Add Supplier to register your first supplier.',
        'col_code' => 'Code',
        'col_business' => 'Business',
        'col_contact' => 'Contact',
        'col_city' => 'City',
        'col_type' => 'Type',
        'col_status' => 'Status',
        'col_categories' => 'Categories',
        'col_created' => 'Created',
        'col_actions' => 'Actions',
    ],

    'filters' => [
        'active' => 'Active suppliers',
        'inactive' => 'Inactive suppliers',
        'by_category' => 'Has categories',
        'by_city' => 'Filter by city',
    ],
];
