<?php

return [
    'actions' => [
        'add' => 'Add Purchase Payment Summary',
        'add_manual' => 'Add manual party',
        'add_source' => 'Add payment source',
        'edit' => 'Edit :number',
        'view' => 'View :number',
        'edit_short' => 'Edit',
        'view_short' => 'View',
        'delete_short' => 'Delete',
        'print_short' => 'Print',
        'print' => 'Save & Print',
        'remove' => 'Remove',
        'delete_confirm' => 'Delete this payment summary sheet?',
    ],

    'fields' => [
        'summary_number' => 'Summary No.',
        'summary_date' => 'Summary Date',
        'paper_size' => 'Paper Size',
        'locale' => 'Sheet Language',
        'notes' => 'Notes',
        'supplier' => 'Supplier',
        'supplier_search' => 'Search supplier',
        'phone' => 'Phone',
        'address' => 'Address',
        'payment_amount' => 'Payment',
        'line_status' => 'Status',
        'source_label' => 'Payment source',
        'source_amount' => 'Amount received',
        'purchaser' => 'Purchaser',
        'created_by' => 'Created by',
        'printed_by' => 'Printed by',
    ],

    'sections' => [
        'suppliers' => 'Supplier Payments',
        'sources' => 'Where payments came from',
    ],

    'locales' => [
        'en' => 'English',
        'ur' => 'Urdu',
    ],

    'line_statuses' => [
        'ok' => 'OK',
        'dispute' => 'Dispute',
    ],

    'hints' => [
        'auto_on_save' => 'Auto generated on save',
        'select_purchaser' => 'Select purchaser',
        'notes' => 'Optional note for this summary sheet',
        'manual_row' => 'Use the plus button for expenses or parties not in the suppliers list.',
        'supplier_search' => 'Search and select a supplier to add a row',
        'manual_name' => 'Party / expense name',
        'sources' => 'Add payment sources with amounts (up to five).',
        'supplier_readonly' => 'Supplier details are read-only here.',
    ],

    'paper_sizes' => [
        'a4' => 'A4',
        'receipt' => 'Roll / Receipt',
    ],

    'filters' => [
        'locale' => 'Sheet language',
    ],

    'columns' => [
        'sr' => 'Sr',
        'supplier' => 'Supplier / Party',
        'payment' => 'Payment',
        'ok' => 'OK',
        'dispute' => 'Dispute',
    ],

    'form' => [
        'empty' => 'Search a supplier above or use + for a manual party.',
    ],

    'totals' => [
        'supplier_payments' => 'Total paid to suppliers',
        'source_payments' => 'Total payment received',
        'balance' => 'Balance / remaining',
    ],

    'list' => [
        'empty' => 'No purchase payment summaries yet. Click Add to create one.',
        'col_number' => 'Summary No.',
        'col_date' => 'Summary Date',
        'col_paid_lines' => 'Paid Lines',
        'col_total' => 'Total Payments',
        'col_locale' => 'Language',
        'col_created' => 'Saved',
        'col_actions' => 'Actions',
    ],

    'messages' => [
        'created' => 'Payment summary :number saved.',
        'updated' => 'Payment summary :number updated.',
        'save_failed' => 'Could not save payment summary. Please check the form and try again.',
        'supplier_exists' => 'This supplier is already in the list.',
    ],

    'validation' => [
        'items_required' => 'Add at least one supplier or manual row.',
        'content_required' => 'Enter at least one payment amount or a payment source amount.',
    ],

    'print' => [
        'title' => 'Purchase Payment Summary',
        'heading' => 'Purchase Payment Summary Sheet',
        'date' => 'Date',
        'print_button' => 'Print',
        'close_button' => 'Close',
        'sources_heading' => 'Payments received from',
        'total_payments' => 'Total paid to suppliers',
        'total_sources' => 'Total payment received',
        'balance' => 'Balance / remaining',
        'notes' => 'Notes',
    ],
];
