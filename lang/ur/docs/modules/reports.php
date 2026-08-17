<?php

return [
    'overview' => [
        'title' => 'Reports & Analytics — جائزہ',
        'summary' => 'لائیو رپورٹس (اسٹاک، لیجر، سیلز سمری، P&L) اور شیل صفحات (خرچ، کیش، موسمی)۔',
        'meta' => [
            'routes' => ['/admin/reports/overview'],
            'permissions' => ['reports.view'],
            'related' => ['purchasing', 'sales', 'finance', 'contacts'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'ہر صفحہ ReportShell + ReportCatalog۔ لائیو: stock، customer/supplier ledger، sales-summary، profit-loss۔ باقی شیل۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'استعمال',
                'steps' => [
                    'رپورٹ کھولیں؛ فلٹر/کالم۔',
                    'View Report؛ لائیو پر CSV Export۔',
                ],
            ],
            [
                'id' => 'rules',
                'type' => 'rules',
                'heading' => 'قواعد',
                'list' => [
                    'صرف reports.view۔',
                    'مدت: today، week، month، quarter، year؛ خالی = all-time۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'expense/cash/seasonal شیل — کوئی *ReportService نہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'livewire' => 'App\\Livewire\\Reports\\ReportShell',
                    'services' => 'App\\Services\\Reports\\ReportCatalog',
                ],
            ],
        ],
    ],

    'stock-report' => [
        'title' => 'Stock Report',
        'summary' => 'لائیو اسٹاک: in/low/out/reorder اور KPIs۔',
        'meta' => [
            'routes' => ['/admin/reports/stock-report'],
            'permissions' => ['reports.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'active products؛ reserved ہمیشہ 0؛ reorder = ReorderService::needsReorder۔',
                ],
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'فلٹر/کالم',
                'list' => [
                    'سرچ؛ stock: all|in|low|out|reorder؛ category۔',
                    'KPIs اور CSV۔',
                ],
            ],
            [
                'id' => 'integrations',
                'type' => 'integrations',
                'heading' => 'ماخذ',
                'list' => [
                    'products؛ ReorderService۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'reserved ہمیشہ 0۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'services' => 'App\\Services\\Reports\\StockReportService',
                ],
            ],
        ],
    ],

    'customer-ledger' => [
        'title' => 'Customer Ledger',
        'summary' => 'کسٹمر opening، debit/credit، بیلنس؛ history modal۔',
        'meta' => [
            'routes' => ['/admin/reports/customer-ledger'],
            'permissions' => ['reports.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'مکمل sales، آن لائن آرڈرز (بغیر sale_id)، returns سے حرکت۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'عمل',
                'steps' => [
                    'period اور customer:all|balance|zero۔',
                    'history modal۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'سپلائر جیسا seedOpening نہیں — خام opening_balance۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'services' => 'App\\Services\\Reports\\CustomerLedgerReportService',
                ],
            ],
        ],
    ],

    'supplier-ledger' => [
        'title' => 'Supplier Ledger',
        'summary' => 'خریدیں + vendor payment transfers؛ seedOpening۔',
        'meta' => [
            'routes' => ['/admin/reports/supplier-ledger'],
            'permissions' => ['reports.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'purchase totals/amount_paid اور TYPE_VENDOR_PAYMENT۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'عمل',
                'steps' => [
                    'period؛ supplier:all|balance|zero؛ history۔',
                ],
            ],
            [
                'id' => 'integrations',
                'type' => 'integrations',
                'heading' => 'روابط',
                'list' => [
                    'Purchases؛ Finance vendor transfers۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'Payment Summary یہاں نہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'services' => 'App\\Services\\Reports\\SupplierLedgerReportService',
                ],
            ],
        ],
    ],

    'sales-summary' => [
        'title' => 'Sales Summary',
        'summary' => 'مکمل POS + returns؛ period/channel/group۔',
        'meta' => [
            'routes' => ['/admin/reports/sales-summary'],
            'permissions' => ['reports.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'gross/returns/net/avg؛ channel sale_id سے۔',
                ],
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'فلٹر',
                'list' => [
                    'period، channel، group۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'بغیر sale_id آن لائن آرڈر یہاں POS قطار نہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'services' => 'App\\Services\\Reports\\SalesSummaryReportService',
                ],
            ],
        ],
    ],

    'profit-loss' => [
        'title' => 'Profit & Loss',
        'summary' => 'سیل قطار: net، COGS، منافع؛ KPI میں ExpensePayment + purchaser_payment + لیگیسی unlinked expense_payment۔',
        'meta' => [
            'routes' => ['/admin/reports/profit-loss'],
            'permissions' => ['reports.view'],
            'related' => ['expense-payments', 'sales'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'مکمل sales قطار؛ اخراجات KPI میں (ٹیبل قطار نہیں)۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'عمل',
                'steps' => [
                    'period؛ result:all|profit|loss۔',
                    'Finance payments پوسٹ رکھیں تاکہ اخراجات میل کھائیں۔',
                ],
            ],
            [
                'id' => 'rules',
                'type' => 'rules',
                'heading' => 'قواعد',
                'list' => [
                    'ExpensePayment، purchaser_payment، اور لیگیسی unlinked expense_payment الگ راستے (linked expense FT دو بار نہیں)۔',
                    'vendor/deposit/withdrawal/عام transfers P&L اخراجات میں نہیں۔',
                ],
            ],
            [
                'id' => 'integrations',
                'type' => 'integrations',
                'heading' => 'روابط',
                'list' => [
                    'ExpensePayment؛ Sales/Returns؛ PurchaseItem COGS۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'linked expense_payment صرف EXPAY سے؛ purchaser_payment اور لیگیسی unlinked FT الگ۔',
                ],
            ],
            [
                'id' => 'acceptance',
                'type' => 'acceptance',
                'heading' => 'Acceptance',
                'acceptance' => [
                    'مکمل سیل قطار میں۔',
                    'ExpensePayment KPI اخراجات بڑھائے۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'services' => 'App\\Services\\Reports\\ProfitLossReportService',
                ],
            ],
        ],
    ],

    'expense-report' => [
        'title' => 'Expenses Report',
        'summary' => 'شیل — سروس نہیں۔ Finance payments / P&L استعمال کریں۔',
        'meta' => [
            'routes' => ['/admin/reports/expense-report'],
            'permissions' => ['reports.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'UI فلٹر/کالم؛ ڈیٹا لوڈ نہیں۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'ExpensePayment پر سروس وائر کریں۔',
                ],
            ],
        ],
    ],

    'cash-report' => [
        'title' => 'Cash Report',
        'summary' => 'شیل — CashReportService نہیں۔',
        'meta' => [
            'routes' => ['/admin/reports/cash-report'],
            'permissions' => ['reports.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'cash/bank فلٹر UI صرف۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'بیک اینڈ نہیں۔',
                ],
            ],
        ],
    ],

    'seasonal-sales' => [
        'title' => 'Seasonal Sales',
        'summary' => 'شیل — موسمی سیل سروس نہیں۔',
        'meta' => [
            'routes' => ['/admin/reports/seasonal-sales'],
            'permissions' => ['reports.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'موسم × مصنوعات کے لیے placeholder۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'کوئی سروس نہیں۔',
                ],
            ],
        ],
    ],

    'seasonal-purchases' => [
        'title' => 'Seasonal Purchases',
        'summary' => 'شیل — موسمی خرید سروس نہیں۔',
        'meta' => [
            'routes' => ['/admin/reports/seasonal-purchases'],
            'permissions' => ['reports.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'موسم × خرید placeholder۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'لائیو تجزیہ: Purchases + Supplier Ledger۔',
                ],
            ],
        ],
    ],
];
