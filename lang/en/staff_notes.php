<?php

return [
    'form' => [
        'create_heading' => 'New note',
        'edit_heading' => 'Edit note',
        'lead' => 'Simple reminders for yourself or a teammate. Mark done when finished.',
    ],

    'fields' => [
        'title' => 'Title',
        'body' => 'Details (optional)',
        'body_placeholder' => 'e.g. Pay electricity bill on the 25th…',
        'assigned_to' => 'Assign / forward to',
        'assigned_none' => 'Only me (no assignee)',
        'due_on' => 'Due date (optional)',
        'forward_to' => 'Forward to staff',
    ],

    'list' => [
        'heading' => 'Your notes',
        'lead' => 'Notes you created or that were sent to you.',
        'empty' => 'No notes yet. Add one on the left.',
        'search' => 'Search notes…',
        'created_by' => 'From :name',
        'assigned_to' => 'To :name',
        'due' => 'Due :date',
        'done_by' => 'Done by :name',
    ],

    'statuses' => [
        'open' => 'Open',
        'done' => 'Done',
    ],

    'filters' => [
        'label' => 'Show',
        'open' => 'Open',
        'mine' => 'Assigned to me',
        'created' => 'Created by me',
        'done' => 'Done',
        'all' => 'All',
    ],

    'actions' => [
        'add' => 'Save note',
        'save' => 'Save changes',
        'cancel_edit' => 'Cancel edit',
        'cancel' => 'Cancel',
        'mark_done' => 'Mark done',
        'reopen' => 'Reopen',
        'edit' => 'Edit',
        'forward' => 'Forward',
        'send_forward' => 'Send',
        'delete' => 'Delete',
        'delete_confirm' => 'Delete this note?',
    ],

    'errors' => [
        'title' => 'Enter a title (max 200 characters).',
        'assignee' => 'Choose a valid staff member.',
        'forbidden' => 'You cannot change this note.',
    ],
];
