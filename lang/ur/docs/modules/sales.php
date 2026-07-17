<?php

return [
    'overview' => [
        'title' => 'Sales & POS — جائزہ',
        'summary' => 'کاؤنٹر سیلز، quotations، returns، آن لائن آرڈرز، اور dispatch قطار۔',
        'meta' => [
            'routes' => ['/admin/sales/overview'],
            'permissions' => ['sales.view'],
            'related' => ['catalog', 'contacts', 'store', 'finance', 'reports'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'POS انوائسز، sale quotations، returns، ویب آرڈرز، اور paid-order dispatch۔',
                    'Overview اعداد/سرچ؛ toolbar صرف سرچ (Add نہیں)۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'راستے',
                'steps' => [
                    'POS: sale مکمل یا hold → پرنٹ۔',
                    'آن لائن: checkout → Online Orders → proof → Dispatch۔',
                    'Returns: مکمل sale نمبر پر → اسٹاک بڑھے۔',
                ],
            ],
            [
                'id' => 'integrations',
                'type' => 'integrations',
                'heading' => 'روابط',
                'list' => [
                    'Catalog، Contacts، Finance (بینک رسید)، Reports۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'Pos\\Terminal unwired؛ لائیو UI = Sales\\Form۔',
                    'صرف cash اور bank_transfer — card نہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Sales\\OverviewPage',
                    'livewire' => 'App\\Livewire\\Sales\\Overview',
                    'services' => [
                        'App\\Services\\Sales\\SalesOverviewService',
                        'App\\Services\\Sales\\SalesSearchService',
                    ],
                ],
            ],
        ],
    ],

    'pos-sales' => [
        'title' => 'Sales & POS',
        'summary' => 'POS انوائس: کارٹ، cash/bank، مکمل پر اسٹاک، بینک کریڈٹ، پرنٹ۔',
        'meta' => [
            'routes' => ['/admin/sales/pos-sales'],
            'permissions' => ['sales.view', 'sales.create', 'sales.edit', 'sales.delete'],
            'related' => ['customer-ledger', 'sales-summary', 'profit-loss'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'فہرست + form panel (Sales\\Form)۔ ایک لائن فی پروڈکٹ؛ QuantityPricingEngine (forPos)۔',
                ],
                'screenshot_placeholder' => true,
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'اہم فیلڈز',
                'fields' => [
                    ['name' => 'sale_number', 'label' => 'نمبر', 'type' => 'string', 'required' => false, 'rules' => ''],
                    ['name' => 'customer_id', 'label' => 'کسٹمر', 'type' => 'relation', 'required' => false, 'rules' => ''],
                    ['name' => 'payment_method', 'label' => 'طریقہ', 'type' => 'enum', 'required' => true, 'rules' => 'cash|bank_transfer'],
                    ['name' => 'bank_account_id', 'label' => 'بینک', 'type' => 'relation', 'required' => false, 'rules' => 'bank_transfer پر'],
                    ['name' => 'payment_status', 'label' => 'ادائیگی حیثیت', 'type' => 'enum', 'required' => true, 'rules' => 'paid|partial|unpaid'],
                    ['name' => 'amount_paid', 'label' => 'ادا شدہ', 'type' => 'decimal', 'required' => false, 'rules' => ''],
                    ['name' => 'status', 'label' => 'سیل حیثیت', 'type' => 'enum', 'required' => true, 'rules' => 'held|completed'],
                    ['name' => 'items.*.qty', 'label' => 'مقدار', 'type' => 'decimal', 'required' => true, 'rules' => ''],
                    ['name' => 'items.*.unit_price', 'label' => 'قیمت', 'type' => 'decimal', 'required' => false, 'rules' => ''],
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'عمل',
                'steps' => [
                    'لائنیں شامل؛ ادائیگی سیٹ۔',
                    'Hold — اسٹاک نہیں۔',
                    'Complete — stock_qty گھٹے؛ bank_transfer پر بینک کریڈٹ۔',
                    'پرنٹ admin.sales.print۔',
                ],
            ],
            [
                'id' => 'rules',
                'type' => 'rules',
                'heading' => 'قواعد',
                'list' => [
                    'sales.create/edit/delete/view حسبِ عمل۔',
                    'مکمل سیل ڈیلیٹ پر اسٹاک واپس۔',
                    'کریڈٹ = بیلنس؛ الگ AR ٹیبل نہیں۔',
                ],
            ],
            [
                'id' => 'integrations',
                'type' => 'integrations',
                'heading' => 'روابط',
                'list' => [
                    'SaleService، BankAccountService، رپورٹس۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'Terminal orphaned۔',
                    'Card payment نہیں۔',
                    'Form میں quick-add کسٹمر نہیں۔',
                ],
            ],
            [
                'id' => 'acceptance',
                'type' => 'acceptance',
                'heading' => 'Acceptance',
                'acceptance' => [
                    'مکمل cash → اسٹاک گھٹے۔',
                    'Hold → اسٹاک نہیں۔',
                    'bank_transfer + amount_paid → بینک کریڈٹ۔',
                    'ڈیلیٹ مکمل → اسٹاک واپس۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Sales\\PosSalesPage',
                    'livewire' => ['App\\Livewire\\Sales\\ListSales', 'App\\Livewire\\Sales\\Form'],
                    'services' => 'App\\Services\\Sales\\SaleService',
                    'models' => ['App\\Models\\Sale', 'App\\Models\\SaleItem'],
                ],
            ],
        ],
    ],

    'sales-quotations' => [
        'title' => 'Sale Quotations',
        'summary' => 'کسٹمر quotation + پرنٹ — اسٹاک/convert نہیں۔',
        'meta' => [
            'routes' => ['/admin/sales/sales-quotations'],
            'permissions' => ['sales.view', 'sales.create', 'sales.edit', 'sales.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'CRUD + پرنٹ؛ stock/ledger نہیں۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'عمل',
                'steps' => [
                    'quotation بنائیں؛ سیو/پرنٹ۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'POS sale میں convert نہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'services' => 'App\\Services\\Sales\\SaleQuotationService',
                    'models' => ['App\\Models\\SaleQuotation', 'App\\Models\\SaleQuotationItem'],
                ],
            ],
        ],
    ],

    'sales-returns' => [
        'title' => 'Sale Returns',
        'summary' => 'مکمل sale پر واپسی؛ اسٹاک بڑھے؛ بینک ریفنڈ لیجر نہیں۔',
        'meta' => [
            'routes' => ['/admin/sales/sales-returns'],
            'permissions' => ['sales.view', 'sales.create', 'sales.edit', 'sales.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'sale نمبر سے واپسی؛ qty حد؛ اسٹاک increment؛ پرنٹ۔',
                ],
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'فیلڈز',
                'fields' => [
                    ['name' => 'sale_id', 'label' => 'اصل سیل', 'type' => 'relation', 'required' => true, 'rules' => ''],
                    ['name' => 'payment_method', 'label' => 'ریفنڈ طریقہ', 'type' => 'enum', 'required' => false, 'rules' => 'cash|bank_transfer'],
                    ['name' => 'bank_account_id', 'label' => 'بینک', 'type' => 'relation', 'required' => false, 'rules' => 'لیجر ڈیبٹ نہیں'],
                    ['name' => 'refund_total', 'label' => 'ریفنڈ ٹوٹل', 'type' => 'decimal', 'required' => false, 'rules' => ''],
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'بینک فیلڈز کے باوجود BankAccountService ریفنڈ نہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'services' => 'App\\Services\\Sales\\SaleReturnService',
                    'models' => ['App\\Models\\SaleReturn', 'App\\Models\\SaleReturnItem'],
                ],
            ],
        ],
    ],

    'online-orders' => [
        'title' => 'Online Sale Orders',
        'summary' => 'WEB- آرڈرز ایڈمن؛ بینک proof تصدیق پر رسید + dispatch۔',
        'meta' => [
            'routes' => ['/admin/sales/online-orders'],
            'permissions' => ['sales.view', 'sales.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'CheckoutController سے تخلیق۔ ایڈمن Add نہیں — دیکھیں/ترمیم؛ proof verify/reject۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'عمل',
                'steps' => [
                    'آن لائن checkout۔',
                    'ایڈمن حیثیت؛ proof → paid + بینک رسید + dispatch اہلیت۔',
                ],
            ],
            [
                'id' => 'rules',
                'type' => 'rules',
                'heading' => 'قواعد',
                'list' => [
                    'آرڈر وقت پر اسٹاک نہیں گھٹتا۔',
                    'customer ledger میں sale_id کے بغیر آرڈرز شامل۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'STATUS_CONVERTED / sale_id UI میں سیٹ نہیں ہوتے۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'livewire' => ['App\\Livewire\\OnlineSaleOrders\\ListOnlineSaleOrders', 'App\\Livewire\\OnlineSaleOrders\\Form'],
                    'services' => 'App\\Services\\Sales\\OnlineSaleOrderService',
                    'models' => ['App\\Models\\OnlineSaleOrder', 'App\\Models\\OnlineSaleOrderItem'],
                ],
            ],
        ],
    ],

    'order-dispatch' => [
        'title' => 'Order Dispatch Queue',
        'summary' => 'paid/partial آن لائن آرڈرز کی قطار؛ مشترکہ فارم۔',
        'meta' => [
            'routes' => ['/admin/sales/order-dispatch'],
            'permissions' => ['sales.view', 'sales.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'سپلائر تفویض، WhatsApp/میل، fulfillment proofs۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'عمل',
                'steps' => [
                    'قطار کھولیں → OnlineSaleOrders\\Form۔',
                    'سپلائر/proof۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'POS convert / shipment ledger نہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'livewire' => 'App\\Livewire\\OnlineSaleOrders\\ListOrderDispatch',
                    'services' => 'App\\Services\\Sales\\OnlineOrderDispatchService',
                ],
            ],
        ],
    ],
];
