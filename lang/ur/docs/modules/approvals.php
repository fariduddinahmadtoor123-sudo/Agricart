<?php

return [
    'overview' => [
        'title' => 'Approvals — جائزہ',
        'summary' => 'منظوری قطاریں؛ فی الحال صرف عملہ کارڈ لائیو۔',
        'meta' => [
            'routes' => ['/admin/approvals/overview'],
            'permissions' => ['approvals.view'],
            'related' => ['staff'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'ApprovalsOverviewService — صرف staff کارڈ (pending/rejected)۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'خرید/سیل/خرچ منظوری قطاریں ابھی نہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'livewire' => 'App\\Livewire\\Approvals\\Overview',
                    'services' => 'App\\Services\\Approvals\\ApprovalsOverviewService',
                ],
            ],
        ],
    ],

    'staff' => [
        'title' => 'Staff Approvals',
        'summary' => 'User منظور/مسترد؛ منظور پر Role۔',
        'meta' => [
            'routes' => ['/admin/approvals/staff'],
            'permissions' => ['approvals.view', 'approvals.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'ListStaffApprovals؛ approve/reject کو approvals.edit چاہیے۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'عمل',
                'steps' => [
                    'pending فلٹر؛ منظور (Role) یا مسترد (وجہ)۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'صرف staff منظوری قسم۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'livewire' => 'App\\Livewire\\Approvals\\ListStaffApprovals',
                    'services' => 'App\\Services\\Approvals\\StaffApprovalService',
                ],
            ],
        ],
    ],
];
