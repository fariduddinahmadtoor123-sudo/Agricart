<?php

return [
    'hint' => 'Reusable customer Inbox messages. English is the default send language; Urdu is used when the admin UI is in Urdu (falls back to English if Urdu is empty).',
    'create' => 'New message template',
    'edit' => 'Edit message template',
    'empty' => 'No message templates yet.',
    'created' => 'Template created.',
    'updated' => 'Template updated.',
    'deleted' => 'Template deleted.',
    'toggled' => 'Template status updated.',
    'forbidden' => 'You do not have permission to manage message templates.',
    'confirm_delete' => 'Delete this template?',
    'placeholder_hint' => 'Write English and Urdu separately. Use :name where the customer name should appear.',

    'fields' => [
        'name' => 'Template name',
        'name_en' => 'Name (English)',
        'name_ur' => 'Name (Urdu)',
        'subject' => 'Message title',
        'subject_en' => 'Title (English)',
        'subject_ur' => 'Title (Urdu)',
        'body' => 'Message details',
        'body_en' => 'Details (English)',
        'body_ur' => 'Details (Urdu)',
        'active' => 'Active (shown in Send message)',
        'status' => 'Status',
        'order' => 'Order',
    ],

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'actions' => [
        'create' => 'Save template',
        'update' => 'Update template',
        'cancel' => 'Cancel',
        'edit' => 'Edit',
        'toggle' => 'Toggle',
        'delete' => 'Delete',
        'up' => 'Up',
        'down' => 'Down',
    ],

    'errors' => [
        'not_found' => 'Template not found.',
        'validation' => 'Please check the template fields.',
        'welcome_locked' => 'The Welcome template cannot be deleted. Deactivate it instead if needed.',
    ],
];
