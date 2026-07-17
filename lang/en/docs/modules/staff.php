<?php

return [
    'overview' => [
        'title' => 'Staff Management — Overview',
        'summary' => 'HR roster, pay assignments, attendance, leave, advances, and payroll roll-ups over User records.',
        'meta' => [
            'routes' => ['/admin/staff/overview'],
            'permissions' => ['staff.view', 'staff.view_self'],
            'related' => ['approvals', 'finance', 'settings'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Staff module manages employees as User records with staff profile fields. Overview Livewire uses StaffOverviewService and StaffSearchService.',
                    'Access is gated by StaffPermissions::canAccessModule() (more granular than a single staff.view check on some pages).',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Typical path',
                'steps' => [
                    'Approve new users under Approvals → Staff when pending.',
                    'Maintain roster on Staff; assign salary on Pay Management.',
                    'Record attendance/leave; run payroll roll; track advances.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Payroll has no dedicated Eloquent payroll model — StaffPayrollService computes over assignments, attendance, advances.',
                    'Salary expense posting to Finance expense heads is not automatic from this overview.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'livewire' => 'App\\Livewire\\Staff\\Overview',
                    'services' => [
                        'App\\Services\\Staff\\StaffOverviewService',
                        'App\\Services\\Staff\\StaffPermissions',
                    ],
                ],
            ],
        ],
    ],

    'staff' => [
        'title' => 'Staff',
        'summary' => 'Employee roster list (User + StaffMemberService) with create/edit via form panel.',
        'meta' => [
            'routes' => ['/admin/staff/staff'],
            'permissions' => ['staff.view', 'staff.create', 'staff.edit', 'staff.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Livewire staff.list-staff-members maintains staff users. New accounts may arrive as pending and require Approvals → Staff before active use.',
                ],
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'Typical fields',
                'list' => [
                    'Identity — name, email/phone as configured on User.',
                    'Role — assigned on approval or edit per StaffApprovalService / role UI.',
                    'Status — pending / active / rejected (aligned with approvals filters).',
                ],
            ],
            [
                'id' => 'integrations',
                'type' => 'integrations',
                'heading' => 'Integrations',
                'list' => [
                    'Approvals → Staff — approve/reject queue.',
                    'Settings → Users/Roles — login and permission sets (separate scope from Staff HR).',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Staff and Settings Users are separate scopes — do not merge modules.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'livewire' => 'App\\Livewire\\Staff\\ListStaffMembers',
                    'models' => 'App\\Models\\User',
                ],
            ],
        ],
    ],

    'pay-management' => [
        'title' => 'Pay Management',
        'summary' => 'Salary assignments (StaffSalaryAssignment) per staff User.',
        'meta' => [
            'routes' => ['/admin/staff/pay-management'],
            'permissions' => ['staff.view', 'staff.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Livewire staff.list-pay-management maintains pay/salary assignment rows used later by payroll calculations.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Assignments do not by themselves post Finance ExpensePayment vouchers.',
                    'Deepen field tables against the Livewire form when rebuilding pay grades.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'models' => ['App\\Models\\StaffSalaryAssignment', 'App\\Models\\User'],
                ],
            ],
        ],
    ],

    'attendance' => [
        'title' => 'Attendance',
        'summary' => 'Daily attendance records (StaffAttendanceRecord) with list + forms.',
        'meta' => [
            'routes' => ['/admin/staff/attendance'],
            'permissions' => ['staff.attendance.view', 'staff.attendance.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Livewire staff.list-daily-attendance records day-level attendance used by leave reports and payroll inputs.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Permission keys are granular (staff.attendance.*) — verify StaffPermissions for self vs all.',
                    'No biometric device integration in current code.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'models' => ['App\\Models\\StaffAttendanceRecord', 'App\\Models\\User'],
                ],
            ],
        ],
    ],

    'leave-management' => [
        'title' => 'Leave Management',
        'summary' => 'Leave reporting over attendance with public holidays and leave settings.',
        'meta' => [
            'routes' => ['/admin/staff/leave-management'],
            'permissions' => ['staff.leave.view_all', 'staff.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Livewire staff.list-staff-leave-report; forms use StaffPublicHoliday and StaffLeaveSetting.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Leave is derived/report-oriented over attendance — confirm request/approval workflow depth in Livewire before assuming a full leave engine.',
                ],
            ],
        ],
    ],

    'advances' => [
        'title' => 'Advances',
        'summary' => 'Staff advance transactions (StaffAdvanceTransaction).',
        'meta' => [
            'routes' => ['/admin/staff/advances'],
            'permissions' => ['staff.advances.view', 'staff.advances.create'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Livewire staff.list-staff-advances records advances deducted in payroll calculations.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Advance cash out may not post Finance wallets automatically — verify StaffAdvanceTransaction service before assuming ledger links.',
                ],
            ],
        ],
    ],

    'payroll' => [
        'title' => 'Payroll',
        'summary' => 'Payroll roll UI via StaffPayrollService — computed, not a stored payroll run model.',
        'meta' => [
            'routes' => ['/admin/staff/payroll'],
            'permissions' => ['staff.payroll.view_all'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Livewire staff.list-staff-payroll + PayrollRollForm compute pay from User, salary assignments, attendance, and advances.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'No dedicated payroll Eloquent model for posted runs.',
                    'Linking net pay to Finance expense payments is a rebuild concern if required for P&L.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'services' => 'App\\Services\\Staff\\StaffPayrollService',
                ],
            ],
        ],
    ],
];
