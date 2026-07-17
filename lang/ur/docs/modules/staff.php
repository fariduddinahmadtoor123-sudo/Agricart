<?php

return [
    'overview' => [
        'title' => 'Staff Management — جائزہ',
        'summary' => 'عملہ، تنخواہ، حاضری، چھٹی، ایڈوانس، پے رول۔',
        'meta' => [
            'routes' => ['/admin/staff/overview'],
            'permissions' => ['staff.view', 'staff.view_self'],
            'related' => ['approvals', 'finance', 'settings'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'عملہ User ریکارڈز پر۔ StaffPermissions سے رسائی۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'راستہ',
                'steps' => [
                    'Approvals میں منظور؛ Staff فہرست؛ تنخواہ؛ حاضری؛ پے رول۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'الگ payroll Eloquent ماڈل نہیں۔',
                    'خودکار Finance expense پوسٹ نہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
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
        'summary' => 'عملہ فہرست (User)۔',
        'meta' => [
            'routes' => ['/admin/staff/staff'],
            'permissions' => ['staff.view', 'staff.create', 'staff.edit', 'staff.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'list-staff-members؛ نئے صارف Approvals سے آ سکتے ہیں۔',
                ],
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'فیلڈز',
                'list' => [
                    'شناخت، Role، status (pending/active/rejected)۔',
                ],
            ],
            [
                'id' => 'integrations',
                'type' => 'integrations',
                'heading' => 'روابط',
                'list' => [
                    'Approvals → Staff؛ Settings Users الگ۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'Staff اور Settings Users الگ دائرے ہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'livewire' => 'App\\Livewire\\Staff\\ListStaffMembers',
                    'models' => 'App\\Models\\User',
                ],
            ],
        ],
    ],

    'pay-management' => [
        'title' => 'Pay Management',
        'summary' => 'StaffSalaryAssignment۔',
        'meta' => [
            'routes' => ['/admin/staff/pay-management'],
            'permissions' => ['staff.view', 'staff.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'تنخواہ تفویض جو پے رول میں استعمال ہوتی ہیں۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'خودکار ExpensePayment نہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'models' => ['App\\Models\\StaffSalaryAssignment', 'App\\Models\\User'],
                ],
            ],
        ],
    ],

    'attendance' => [
        'title' => 'Attendance',
        'summary' => 'روزانہ حاضری (StaffAttendanceRecord)۔',
        'meta' => [
            'routes' => ['/admin/staff/attendance'],
            'permissions' => ['staff.attendance.view', 'staff.attendance.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'یومیہ ریکارڈ؛ پے رول/چھٹی ان پٹ۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'بائیو میٹرک انٹیگریشن نہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'models' => ['App\\Models\\StaffAttendanceRecord', 'App\\Models\\User'],
                ],
            ],
        ],
    ],

    'leave-management' => [
        'title' => 'Leave Management',
        'summary' => 'چھٹی رپورٹ؛ تعطیلات/سیٹنگز۔',
        'meta' => [
            'routes' => ['/admin/staff/leave-management'],
            'permissions' => ['staff.leave.view_all', 'staff.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'حاضری پر مبنی leave رپورٹ۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'مکمل leave انجن فرض نہ کریں — Livewire چیک کریں۔',
                ],
            ],
        ],
    ],

    'advances' => [
        'title' => 'Advances',
        'summary' => 'StaffAdvanceTransaction۔',
        'meta' => [
            'routes' => ['/admin/staff/advances'],
            'permissions' => ['staff.advances.view', 'staff.advances.create'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'ایڈوانس جو پے رول میں کٹتے ہیں۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'Finance والٹ خودکار پوسٹ تصدیق کریں۔',
                ],
            ],
        ],
    ],

    'payroll' => [
        'title' => 'Payroll',
        'summary' => 'StaffPayrollService حساب — الگ payroll ماڈل نہیں۔',
        'meta' => [
            'routes' => ['/admin/staff/payroll'],
            'permissions' => ['staff.payroll.view_all'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'تنخواہ + حاضری + ایڈوانس سے حساب۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'P&L کے لیے Finance لنک rebuild موضوع ہے۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'services' => 'App\\Services\\Staff\\StaffPayrollService',
                ],
            ],
        ],
    ],
];
