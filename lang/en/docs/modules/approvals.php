<?php

return [
    'overview' => [
        'title' => 'Approvals — Overview',
        'summary' => 'Pending staff (and future) approval queues. Currently only the staff card is live.',
        'meta' => [
            'routes' => ['/admin/approvals/overview'],
            'permissions' => ['approvals.view'],
            'related' => ['staff'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'ApprovalsOverviewService feeds stat cards. Today only the staff card is populated (pending/rejected User counts) with a link to Approvals → Staff.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'No purchase/sale/expense approval queues in this cluster yet — only overview + staff pages.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'livewire' => 'App\\Livewire\\Approvals\\Overview',
                    'services' => 'App\\Services\\Approvals\\ApprovalsOverviewService',
                ],
            ],
        ],
    ],

    'staff' => [
        'title' => 'Staff Approvals',
        'summary' => 'Approve or reject pending Users; assign Role on approve.',
        'meta' => [
            'routes' => ['/admin/approvals/staff'],
            'permissions' => ['approvals.view', 'approvals.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'ListStaffApprovals paginates Users with status filter (pending|active|rejected). View modal; approve with Role; reject with reason via StaffApprovalService.',
                    'approvals.edit required for canManage() approve/reject actions.',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Workflow',
                'steps' => [
                    'Filter pending staff.',
                    'Review profile → Approve (pick Role) or Reject (reason).',
                    'Approved users become usable in Staff module / login per role permissions.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Toolbar events staff-approvals-*; no other approval resource types registered.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'livewire' => 'App\\Livewire\\Approvals\\ListStaffApprovals',
                    'services' => 'App\\Services\\Approvals\\StaffApprovalService',
                ],
            ],
        ],
    ],
];
