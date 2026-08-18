<?php

return [
    'overview' => [
        'title' => 'Purchasing & Inventory — جائزہ',
        'summary' => 'آنے والا اسٹاک اور سپلائر دستاویزات: planning، quotations، purchases، payment summaries، re-order، price tags۔',
        'meta' => [
            'routes' => ['/admin/purchasing/overview'],
            'permissions' => ['purchasing.view'],
            'related' => ['contacts', 'catalog', 'reports', 'marketplace'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'Purchasing سپلائرز سے موصول سامان اور پرنٹ دستاویزات ریکارڈ کرتا ہے۔ صرف Purchases جن کی delivery partial یا received ہو product stock بدلتی ہیں۔',
                    'Overview کیشڈ اعداد و سرچ دکھاتا ہے (planning، quotations، purchases، re-orders، price-tag readiness)۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'عمومی راستہ',
                'steps' => [
                    'اختیاری: planning sheet یا quotation پرنٹ کے لیے۔',
                    'Purchase بنائیں: سپلائر، لائنیں، payment اور delivery status۔',
                    'partial/received پر اسٹاک بڑھے اور متعلقہ reorder queue fulfill ہو۔',
                    'Re-Order Center سے قلت قطار میں بھیجیں؛ Price Tags سے لیبل پرنٹ کریں۔',
                ],
            ],
            [
                'id' => 'integrations',
                'type' => 'integrations',
                'heading' => 'روابط',
                'list' => [
                    'Contacts → Suppliers',
                    'Catalog → Products (stock_qty، channel pricing)',
                    'Reports → Stock / Supplier Ledger',
                    'Marketplace — ChannelPricingEngine',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'purchasing.overview.stats کیش سیو پر صاف نہیں ہوتا۔',
                    'Payment Summary کا overview کارڈ/سرچ نہیں۔',
                    'Planning → quotation → purchase خودکار convert نہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Purchasing\\OverviewPage',
                    'livewire' => 'App\\Livewire\\Purchasing\\Overview',
                    'services' => [
                        'App\\Services\\Purchasing\\PurchasingOverviewService',
                        'App\\Services\\Purchasing\\PurchasingSearchService',
                    ],
                ],
            ],
        ],
    ],

    'purchase-planning' => [
        'title' => 'Purchase Planning',
        'summary' => 'پرنٹablе planning sheets (PLN-#####) — اسٹاک/لیجر نہیں۔',
        'meta' => [
            'routes' => ['/admin/purchasing/purchase-planning'],
            'permissions' => ['purchasing.view', 'purchasing.create', 'purchasing.edit', 'purchasing.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'خریداروں کے لیے planning دستاویزات۔ سیو سے اسٹاک یا سپلائر بیلنس نہیں بدلتا۔',
                ],
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'فیلڈز',
                'fields' => [
                    ['name' => 'planning_number', 'label' => 'نمبر', 'type' => 'string', 'required' => false, 'rules' => 'Auto PLN-#####'],
                    ['name' => 'paper_size', 'label' => 'کاغذ', 'type' => 'enum', 'required' => true, 'rules' => 'a4|a5|a3'],
                    ['name' => 'locale', 'label' => 'زبان', 'type' => 'enum', 'required' => true, 'rules' => 'en|ur'],
                    ['name' => 'notes', 'label' => 'نوٹس', 'type' => 'text', 'required' => false, 'rules' => ''],
                    ['name' => 'items.*.purchase_qty', 'label' => 'خرید مقدار', 'type' => 'string', 'required' => false, 'rules' => ''],
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'عمل',
                'steps' => [
                    'شیٹ شامل کریں؛ مصنوعات تلاش/سکین۔',
                    'Save یا Save & Print۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'stock_qty لائن پر literal 0 — لائیو اسٹاک نہیں۔',
                    'Purchase میں convert نہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'livewire' => ['App\\Livewire\\PurchasePlanning\\ListPlanningSheets', 'App\\Livewire\\PurchasePlanning\\Form'],
                    'services' => 'App\\Services\\Purchasing\\PurchasePlanningService',
                    'models' => ['App\\Models\\PurchasePlanningSheet', 'App\\Models\\PurchasePlanningItem'],
                ],
            ],
        ],
    ],

    'purchase-quotations' => [
        'title' => 'Purchase Quotations',
        'summary' => 'سپلائر quotation (QUO-#####) — اسٹاک/AP نہیں۔',
        'meta' => [
            'routes' => ['/admin/purchasing/purchase-quotations'],
            'permissions' => ['purchasing.view', 'purchasing.create', 'purchasing.edit', 'purchasing.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'سپلائر quotations لائنوں کے ساتھ؛ پرنٹ admin.purchase-quotations.print۔',
                ],
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'فیلڈز',
                'fields' => [
                    ['name' => 'quotation_number', 'label' => 'نمبر', 'type' => 'string', 'required' => false, 'rules' => 'QUO-#####'],
                    ['name' => 'supplier_id', 'label' => 'سپلائر', 'type' => 'relation', 'required' => true, 'rules' => ''],
                    ['name' => 'quotation_date', 'label' => 'تاریخ', 'type' => 'date', 'required' => true, 'rules' => ''],
                    ['name' => 'locale', 'label' => 'زبان', 'type' => 'enum', 'required' => true, 'rules' => 'en|ur'],
                    ['name' => 'items.*.required_qty', 'label' => 'مطلوبہ مقدار', 'type' => 'string', 'required' => false, 'rules' => ''],
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'Purchase میں convert نہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'services' => 'App\\Services\\Purchasing\\PurchaseQuotationService',
                    'models' => ['App\\Models\\PurchaseQuotation', 'App\\Models\\PurchaseQuotationItem'],
                ],
            ],
        ],
    ],

    'purchases' => [
        'title' => 'Purchases',
        'summary' => 'وصولی (PUR-#####): سپلائر AP، partial/received پر اسٹاک، اختیاری channel pricing۔',
        'meta' => [
            'routes' => ['/admin/purchasing/purchases'],
            'permissions' => ['purchasing.view', 'purchasing.create', 'purchasing.edit', 'purchasing.delete'],
            'related' => ['re-order-center', 'supplier-ledger', 'stock-report'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'مرکزی inbound دستاویز۔ الگ Post نہیں — delivery partial/received پر سیو سے اسٹاک لگتا ہے؛ pending پر نہیں۔',
                ],
                'screenshot_placeholder' => true,
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'ہیڈر اور لائن فیلڈز',
                'fields' => [
                    ['name' => 'purchase_number', 'label' => 'نمبر', 'type' => 'string', 'required' => false, 'rules' => 'PUR-#####'],
                    ['name' => 'supplier_id', 'label' => 'سپلائر', 'type' => 'relation', 'required' => true, 'rules' => 'opening_balance دکھاتا ہے'],
                    ['name' => 'supplier_balance_snapshot', 'label' => 'بیلنس سنیپ شاٹ', 'type' => 'decimal', 'required' => false, 'rules' => ''],
                    ['name' => 'purchase_date', 'label' => 'تاریخ', 'type' => 'datetime', 'required' => true, 'rules' => 'UI میں date picker نہیں'],
                    ['name' => 'locale', 'label' => 'زبان', 'type' => 'enum', 'required' => true, 'rules' => 'en|ur'],
                    ['name' => 'purchaser_id', 'label' => 'خریدار', 'type' => 'relation', 'required' => false, 'rules' => ''],
                    ['name' => 'vendor_bill_path', 'label' => 'وینڈر بل', 'type' => 'file', 'required' => false, 'rules' => ''],
                    ['name' => 'delivery_status', 'label' => 'ڈیلیوری', 'type' => 'enum', 'required' => true, 'rules' => 'pending|partial|received'],
                    ['name' => 'payment_status', 'label' => 'ادائیگی', 'type' => 'enum', 'required' => true, 'rules' => 'pending|partial|paid'],
                    ['name' => 'dispute_status', 'label' => 'تنازع', 'type' => 'enum', 'required' => true, 'rules' => 'ok|disputed'],
                    ['name' => 'amount_paid', 'label' => 'ادا شدہ', 'type' => 'decimal', 'required' => false, 'rules' => ''],
                    ['name' => 'items.*.purchase_qty', 'label' => 'مقدار', 'type' => 'decimal', 'required' => true, 'rules' => 'partial بھی مکمل qty'],
                    ['name' => 'items.*.current_rate', 'label' => 'ریٹ', 'type' => 'decimal', 'required' => false, 'rules' => ''],
                    ['name' => 'items.*.landing_cost', 'label' => 'لینڈنگ', 'type' => 'decimal', 'required' => false, 'rules' => ''],
                    ['name' => 'items.*.sale_price', 'label' => 'سیل قیمت', 'type' => 'decimal', 'required' => false, 'rules' => ''],
                    ['name' => 'items.*.line_total', 'label' => 'لائن ٹوٹل', 'type' => 'decimal', 'required' => false, 'rules' => ''],
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'سیو پائپ لائن',
                'steps' => [
                    'validate؛ PUR-#####۔',
                    'ہیڈر + لائنیں replace۔',
                    'applySupplierLedger — opening_balance۔',
                    'applyChannelPricing اگر tiers ہوں۔',
                    'partial/received → applyPurchaseStock + fulfillQueueForProducts۔',
                ],
            ],
            [
                'id' => 'rules',
                'type' => 'rules',
                'heading' => 'قواعد',
                'list' => [
                    'pending delivery → اسٹاک نہیں۔',
                    'partial/received دونوں مکمل purchase_qty۔',
                    'payment status خودکار amount_paid سے نہیں (سوائے paid)۔',
                    'ڈیلیٹ پر اسٹاک/لیجر ریورس۔',
                ],
            ],
            [
                'id' => 'integrations',
                'type' => 'integrations',
                'heading' => 'روابط',
                'list' => [
                    'ReorderService، ChannelPricingEngine، Supplier Ledger، Price Tags۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'تاریخ UI میں قابلِ ترمیم نہیں۔',
                    'AP صرف suppliers.opening_balance؛ Payment Summary سے لنک نہیں۔',
                    'amount_paid Finance کیف/بینک پر پوسٹ نہیں۔',
                    'inventory:rebuild-stock اختیاری reconciliation۔',
                ],
            ],
            [
                'id' => 'acceptance',
                'type' => 'acceptance',
                'heading' => 'Acceptance',
                'acceptance' => [
                    'received purchase → stock بڑھے۔',
                    'جزوی ادائیگی → سپلائر بیلنس بڑھے۔',
                    'reorder queue fulfill ہو۔',
                    'received→pending ترمیم → اسٹاک ریورس۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'page' => 'App\\Filament\\Clusters\\Purchasing\\PurchasesPage',
                    'livewire' => ['App\\Livewire\\Purchases\\ListPurchases', 'App\\Livewire\\Purchases\\Form'],
                    'services' => [
                        'App\\Services\\Purchasing\\PurchaseService',
                        'App\\Services\\Purchasing\\ReorderService',
                    ],
                    'models' => ['App\\Models\\Purchase', 'App\\Models\\PurchaseItem', 'App\\Models\\Supplier', 'App\\Models\\Product'],
                ],
            ],
        ],
    ],

    'purchase-payment-summary' => [
        'title' => 'Purchase Payment Summary',
        'summary' => 'پرنٹablе PPS-##### — سپلائر بیلنس اپڈیٹ نہیں۔',
        'meta' => [
            'routes' => ['/admin/purchasing/purchase-payment-summary'],
            'permissions' => ['purchasing.view', 'purchasing.create', 'purchasing.edit', 'purchasing.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'پانچ payment_sources اور سپلائر لائنیں پرنٹ کے لیے۔',
                ],
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'فیلڈز',
                'fields' => [
                    ['name' => 'summary_number', 'label' => 'نمبر', 'type' => 'string', 'required' => false, 'rules' => 'PPS-#####'],
                    ['name' => 'paper_size', 'label' => 'کاغذ', 'type' => 'enum', 'required' => true, 'rules' => 'a4|receipt'],
                    ['name' => 'payment_sources', 'label' => 'ماخذ', 'type' => 'array', 'required' => false, 'rules' => 'Max 5'],
                    ['name' => 'items.*.payment_amount', 'label' => 'رقم', 'type' => 'decimal', 'required' => false, 'rules' => ''],
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'opening_balance / purchase amount_paid اپڈیٹ نہیں۔',
                    'supplierRowsForNewForm() Livewire میں استعمال نہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'services' => 'App\\Services\\Purchasing\\PurchasePaymentSummaryService',
                    'models' => ['App\\Models\\PurchasePaymentSummary', 'App\\Models\\PurchasePaymentSummaryItem'],
                ],
            ],
        ],
    ],

    're-order-center' => [
        'title' => 'Purchase Re-Orders',
        'summary' => 'Reorder امیدوار اور قطار (ROQ-#####)۔ اسٹاک صرف purchase وصولی پر۔',
        'meta' => [
            'routes' => ['/admin/purchasing/re-order-center'],
            'permissions' => ['purchasing.view', 'purchasing.create'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'ٹیبز: Reorder اور Queue۔ منتخب کو قطار میں بھیجیں؛ پرنٹ۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'عمل',
                'steps' => [
                    'فلٹر کریں؛ قطار میں بھیجیں (purchasing.create)۔',
                    'purchase partial/received پر pending items fulfill۔',
                ],
            ],
            [
                'id' => 'rules',
                'type' => 'rules',
                'heading' => 'قواعد',
                'list' => [
                    'قطار بھیجنا اسٹاک نہیں بدلتا۔',
                    'STALE_DAYS = 7۔',
                    'Add بٹن نہیں۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'دستی cancel/fulfill UI نہیں (سوائے purchase)۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'livewire' => 'App\\Livewire\\ReOrder\\ReOrderCenter',
                    'services' => 'App\\Services\\Purchasing\\ReorderService',
                    'models' => ['App\\Models\\ReorderQueueBatch', 'App\\Models\\ReorderQueueItem'],
                ],
            ],
        ],
    ],

    'price-tags' => [
        'title' => 'Price Tags',
        'summary' => 'لیبل لائنیں اور بارکوڈ/QR پرنٹ — اسٹاک نہیں۔',
        'meta' => [
            'routes' => ['/admin/purchasing/price-tags'],
            'permissions' => ['purchasing.view', 'purchasing.create'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'مصنوعات/انوائس سے لائنیں؛ پرنٹ۔ mutations کو purchasing.create چاہیے۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'عمل',
                'steps' => [
                    'readiness فلٹر؛ لائنیں شامل؛ پرنٹ۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'لاگت آخری purchase rate سے ہو سکتی ہے — پرنٹ سے پہلے چیک کریں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'livewire' => 'App\\Livewire\\PriceTags\\ListPriceTags',
                    'services' => ['App\\Services\\Purchasing\\PriceTagService', 'App\\Services\\Printing\\LabelRenderService'],
                ],
            ],
        ],
    ],

    'rate-list' => [
        'title' => 'شاپ ریٹ لسٹ',
        'summary' => 'ریٹیل ریٹ والی چیزوں کی دیوار والی ریٹ لسٹ — انگریزی، اردو، یا دونوں، نام کے اے سے زیڈ حساب سے۔',
        'meta' => [
            'routes' => ['/admin/purchasing/rate-list'],
            'permissions' => ['purchasing.view', 'purchasing.print'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'مقصد',
                'paragraphs' => [
                    'دکان پر لگانے کے لیے ریٹ لسٹ۔ صرف ریٹ والی فعال مصنوعات شامل ہوتی ہیں، نام کے اے سے زیڈ حساب سے۔',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'عمل',
                'steps' => [
                    'Purchasing → Rate List کھولیں۔',
                    'انگریزی، اردو، یا دونوں منتخب کریں۔',
                    'پرنٹ؛ ہر A4 صفحے پر تقریباً 30 مصنوعات (نمبر شمار، نام، ریٹ)۔',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild نوٹس',
                'list' => [
                    'یہ اسٹیکر نہیں — لیبل کے لیے Price Tags استعمال کریں۔',
                    'بغیر ریٹ والی چیزیں شامل نہیں ہوتیں۔ اسٹاک ضروری نہیں۔',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'تکنیکی',
                'classes' => [
                    'livewire' => 'App\\Livewire\\RateLists\\ListRateLists',
                    'services' => 'App\\Services\\Purchasing\\ShopRateListService',
                    'print' => 'admin.rate-list.print',
                ],
            ],
        ],
    ],
];
