<?php

return [
    'overview' => [
        'title' => 'Reports & Analytics — Overview',
        'summary' => 'Live reports (stock, ledgers, sales summary, P&L) plus shell pages for expenses, cash, and seasonal views.',
        'meta' => [
            'routes' => ['/admin/reports/overview'],
            'permissions' => ['reports.view'],
            'related' => ['purchasing', 'sales', 'finance', 'contacts'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Each report page mounts Livewire reports.report-shell with a report-key from ReportCatalog. Shared toolbar: search, filters, columns, View Report; Export CSV only on live reports.',
                    'Live keys auto-load on mount: stock-report, customer-ledger, supplier-ledger, sales-summary, profit-loss. Shell keys show empty preview until a backend exists.',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'How to use',
                'steps' => [
                    'Open a report from sub-nav or overview links.',
                    'Set filters (period, party, category, channel, etc.) and visible columns.',
                    'View Report refreshes live data; Export CSV streams UTF-8 BOM for live reports only.',
                ],
            ],
            [
                'id' => 'rules',
                'type' => 'rules',
                'heading' => 'Rules',
                'list' => [
                    'Module gate: reports.view (no per-report permission keys).',
                    'period:custom is listed in catalog but services only map today|week|month|quarter|year; custom/empty → all-time.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'expense-report, cash-report, seasonal-sales, seasonal-purchases are UI shells (BaseReportModulePage + ReportShell) with no *ReportService — View Report shows view_report_pending.',
                    'DocumentationRegistry treats shells as implemented because they are not BasePlaceholderPage.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'livewire' => 'App\\Livewire\\Reports\\ReportShell',
                    'services' => 'App\\Services\\Reports\\ReportCatalog',
                ],
            ],
        ],
    ],

    'stock-report' => [
        'title' => 'Stock Report',
        'summary' => 'Live on-hand stock by product with in/low/out/reorder filters and KPIs.',
        'meta' => [
            'routes' => ['/admin/reports/stock-report'],
            'permissions' => ['reports.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Lists active products with stock_qty, status from alert_qty (in/low/out), available = on_hand, reserved hardcoded 0, reorder via ReorderService::needsReorder.',
                ],
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'Filters & columns',
                'list' => [
                    'Search — name / SKU / code.',
                    'stock: all|in|low|out|reorder; category:{id}.',
                    'KPIs — total SKUs, in/low/out counts, on reorder, sum on-hand.',
                    'CSV export respects visible columns.',
                ],
            ],
            [
                'id' => 'integrations',
                'type' => 'integrations',
                'heading' => 'Data sources',
                'list' => [
                    'products (+ categories, units).',
                    'Purchasing ReorderService for reorder flag.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'reserved always 0 — no reservation engine wired.',
                    'Stock is runtime product.stock_qty, not a separate movements table.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'services' => 'App\\Services\\Reports\\StockReportService',
                ],
            ],
        ],
    ],

    'customer-ledger' => [
        'title' => 'Customer Ledger',
        'summary' => 'Per-customer opening, period debit/credit, balance; drill-down history modal.',
        'meta' => [
            'routes' => ['/admin/reports/customer-ledger'],
            'permissions' => ['reports.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Summary rows per Customer. Opening = opening_balance + movement before period. Debits: completed Sale.total_amount + OnlineSaleOrder.total_amount (not cancelled, sale_id null) + return amount_paid. Credits: sale/order amount_paid + return refund_total. Balance = opening + debit − credit.',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Workflow',
                'steps' => [
                    'Filter period and customer:all|balance|zero.',
                    'Open party history modal for line-level sales, online orders, returns with running balance.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Unlike supplier ledger, no seedOpening() to strip lifetime movement from stored opening_balance — raw opening_balance is used.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'services' => 'App\\Services\\Reports\\CustomerLedgerReportService',
                ],
            ],
        ],
    ],

    'supplier-ledger' => [
        'title' => 'Supplier Ledger',
        'summary' => 'Payables from purchases and vendor payment fund transfers; seedOpening avoids double-counting opening_balance.',
        'meta' => [
            'routes' => ['/admin/reports/supplier-ledger'],
            'permissions' => ['reports.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'seedOpening = suppliers.opening_balance − lifetime purchases + payments. Period movement: purchase totals (debit), amount_paid (credit), FundTransfer TYPE_VENDOR_PAYMENT to destination_supplier_id (credit).',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Workflow',
                'steps' => [
                    'Filter period and supplier:all|balance|zero.',
                    'History modal: purchases + vendor payment transfers.',
                ],
            ],
            [
                'id' => 'integrations',
                'type' => 'integrations',
                'heading' => 'Integrations',
                'list' => [
                    'Purchases module (total_amount / amount_paid).',
                    'Finance fund transfers typed as vendor payment.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Purchase Payment Summary documents do not appear here.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'services' => 'App\\Services\\Reports\\SupplierLedgerReportService',
                ],
            ],
        ],
    ],

    'sales-summary' => [
        'title' => 'Sales Summary',
        'summary' => 'Completed POS sales and returns by period, channel, and group (day/month/category/product).',
        'meta' => [
            'routes' => ['/admin/reports/sales-summary'],
            'permissions' => ['reports.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'KPIs and grouped rows from completed Sale + SaleReturn in range. Channel POS vs online via existence of online_sale_orders.sale_id. Metrics: invoices, gross, returns, net, avg ticket.',
                ],
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'Filters',
                'list' => [
                    'period: today|week|month|quarter|year (custom → all-time).',
                    'channel and group filters from ReportCatalog.',
                    'Search filters group label text.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Online orders without conversion (no sale_id) are not POS invoice rows; they appear in customer ledger separately.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'services' => 'App\\Services\\Reports\\SalesSummaryReportService',
                ],
            ],
        ],
    ],

    'profit-loss' => [
        'title' => 'Profit & Loss',
        'summary' => 'Per completed sale: net after returns, COGS from purchase cost timeline, gross profit; KPIs subtract operating expenses (ExpensePayment + TYPE_EXPENSE_PAYMENT transfers).',
        'meta' => [
            'routes' => ['/admin/reports/profit-loss'],
            'permissions' => ['reports.view'],
            'related' => ['expense-payments', 'sales'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Table rows are completed sales: gross = total_amount; returns = linked SaleReturn.refund_total; net = gross − returns; COGS from PurchaseItem cost_price else current_rate as-of sale date; profit = net − COGS; result profit/loss/even.',
                    'KPIs sum invoice gross profit then subtract operating expenses: ExpensePayment.amount by payment_date plus FundTransfer TYPE_EXPENSE_PAYMENT by transaction_date. Expenses are not table rows.',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Workflow',
                'steps' => [
                    'Filter period and result:all|profit|loss.',
                    'Ensure Finance expense payments are posted so operating expenses match cash out.',
                ],
            ],
            [
                'id' => 'rules',
                'type' => 'rules',
                'heading' => 'Rules',
                'list' => [
                    'Expense payments and expense-type fund transfers are separate paths (not double-counted).',
                    'Vendor payments and generic cash/bank transfers are not included in P&L expenses.',
                ],
            ],
            [
                'id' => 'integrations',
                'type' => 'integrations',
                'heading' => 'Integrations',
                'list' => [
                    'Finance → Expense Payments (ExpensePayment).',
                    'Sales / Sale Returns / Purchase items for COGS.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Only TYPE_EXPENSE_PAYMENT fund transfers count — not all fund transfers.',
                    'COGS depends on purchase cost history; missing costs skew margin.',
                ],
            ],
            [
                'id' => 'acceptance',
                'type' => 'acceptance',
                'heading' => 'Acceptance',
                'acceptance' => [
                    'Completed sale in period appears as a P&L row with net and COGS.',
                    'Posted ExpensePayment in period increases operating expenses KPI and reduces net_profit.',
                    'result:loss filter excludes profit and even rows.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'services' => 'App\\Services\\Reports\\ProfitLossReportService',
                ],
            ],
        ],
    ],

    'expense-report' => [
        'title' => 'Expenses Report',
        'summary' => 'Shell only — catalog filters/columns; no ExpenseReportService yet. Use Finance payments and P&L for live expense totals.',
        'meta' => [
            'routes' => ['/admin/reports/expense-report'],
            'permissions' => ['reports.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'UI shell with filters expense:all|operating|other and period groups. Intended columns: date, category, reference, description, amount.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'isLiveReport is false — View Report shows pending message; no CSV.',
                    'Wire a service over ExpensePayment (+ categories/heads) before treating as live.',
                    'Until then, rely on Finance → Payments and Reports → Profit & Loss operating expenses.',
                ],
            ],
        ],
    ],

    'cash-report' => [
        'title' => 'Cash Report',
        'summary' => 'Shell only — filters for cash/bank and period; no CashReportService.',
        'meta' => [
            'routes' => ['/admin/reports/cash-report'],
            'permissions' => ['reports.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Catalog UI for cash:all|hand|bank with intended columns date, account, type, reference, cash_in/out, balance.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'No backend. Future source likely Cash Book / bank transactions / transfers — not implemented on this page.',
                ],
            ],
        ],
    ],

    'seasonal-sales' => [
        'title' => 'Seasonal Sales',
        'summary' => 'Shell only — channel + period filters; no seasonal sales service.',
        'meta' => [
            'routes' => ['/admin/reports/seasonal-sales'],
            'permissions' => ['reports.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Placeholder for season × product sales metrics (qty_sold, net_sales, avg_price).',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'No service. Catalog Hijri/seasonal marketing icons are unrelated and unwired for reporting.',
                ],
            ],
        ],
    ],

    'seasonal-purchases' => [
        'title' => 'Seasonal Purchases',
        'summary' => 'Shell only — supplier + period filters; no seasonal purchases service.',
        'meta' => [
            'routes' => ['/admin/reports/seasonal-purchases'],
            'permissions' => ['reports.view'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Placeholder for season × product purchase metrics (qty_purchased, purchase_value, supplier).',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'No service. Live purchase analysis today is Purchases list + Supplier Ledger + Stock Report.',
                ],
            ],
        ],
    ],
];
