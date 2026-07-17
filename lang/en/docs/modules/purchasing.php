<?php

return [
    'overview' => [
        'title' => 'Purchasing & Inventory — Overview',
        'summary' => 'Inbound stock and supplier documents: planning, quotations, purchases, payment summaries, re-order queue, and price tags.',
        'meta' => [
            'routes' => ['/admin/purchasing/overview'],
            'permissions' => ['purchasing.view'],
            'related' => ['contacts', 'catalog', 'reports', 'marketplace'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Purchasing records goods received from suppliers and supporting print documents. Only Purchases with delivery status partial or received change product stock.',
                    'Overview shows cached stat cards (planning, quotations, purchases, re-orders, price-tag readiness) and module search across purchase/planning/quotation numbers and products.',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Typical path',
                'steps' => [
                    'Optional: build a planning sheet or quotation for print.',
                    'Create a Purchase with supplier, lines, payment and delivery statuses.',
                    'When delivery is partial/received, stock increases and pending reorder queue items for those products are fulfilled.',
                    'Use Re-Order Center to queue shortages; use Price Tags to print labels after receipt.',
                ],
            ],
            [
                'id' => 'integrations',
                'type' => 'integrations',
                'heading' => 'Connections',
                'list' => [
                    'Contacts → Suppliers — purchase headers and payment-summary lines.',
                    'Catalog → Products — line items, stock_qty, channel pricing on save.',
                    'Reports → Stock / Supplier Ledger — stock and AP movement from purchases.',
                    'Marketplace — ChannelPricingEngine may update product retail/cost from purchase rate.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Overview cache key purchasing.overview.stats is not invalidated when purchases save.',
                    'No overview card or search for Purchase Payment Summary.',
                    'Planning → quotation → purchase is manual; no convert actions.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
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
        'summary' => 'Printable planning sheets (PLN-#####) with product lines — no stock or ledger posting.',
        'meta' => [
            'routes' => ['/admin/purchasing/purchase-planning'],
            'permissions' => ['purchasing.view', 'purchasing.create', 'purchasing.edit', 'purchasing.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Create and print planning documents for buyers. Saving does not change stock or supplier balances.',
                ],
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'Fields',
                'fields' => [
                    ['name' => 'planning_number', 'label' => 'Planning number', 'type' => 'string', 'required' => false, 'rules' => 'Auto PLN-##### if blank'],
                    ['name' => 'paper_size', 'label' => 'Paper size', 'type' => 'enum', 'required' => true, 'rules' => 'a4|a5|a3'],
                    ['name' => 'locale', 'label' => 'Locale', 'type' => 'enum', 'required' => true, 'rules' => 'en|ur'],
                    ['name' => 'notes', 'label' => 'Notes', 'type' => 'text', 'required' => false, 'rules' => ''],
                    ['name' => 'items.*.purchase_qty', 'label' => 'Purchase qty', 'type' => 'string', 'required' => false, 'rules' => 'Along with stock_qty, required_qty, low_qty, landing_price, sale_price'],
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Workflow',
                'steps' => [
                    'Add sheet via toolbar; add products by search/scan.',
                    'Save & Close or Save & Print (admin.purchase-planning.print).',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'rowFromProduct sets stock_qty to literal 0 — not live product stock.',
                    'No action to create a Purchase from a planning sheet.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
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
        'summary' => 'Supplier quotation documents (QUO-#####) for print — no stock or AP posting.',
        'meta' => [
            'routes' => ['/admin/purchasing/purchase-quotations'],
            'permissions' => ['purchasing.view', 'purchasing.create', 'purchasing.edit', 'purchasing.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Record supplier quotations with product lines (required_qty, previous_price). Print via admin.purchase-quotations.print.',
                ],
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'Fields',
                'fields' => [
                    ['name' => 'quotation_number', 'label' => 'Quotation number', 'type' => 'string', 'required' => false, 'rules' => 'Auto QUO-#####'],
                    ['name' => 'supplier_id', 'label' => 'Supplier', 'type' => 'relation', 'required' => true, 'rules' => 'Active supplier'],
                    ['name' => 'quotation_date', 'label' => 'Date', 'type' => 'date', 'required' => true, 'rules' => ''],
                    ['name' => 'locale', 'label' => 'Locale', 'type' => 'enum', 'required' => true, 'rules' => 'en|ur'],
                    ['name' => 'items.*.required_qty', 'label' => 'Required qty', 'type' => 'string', 'required' => false, 'rules' => 'current_price normalized null'],
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'No convert-to-purchase workflow.',
                    'Lines do not carry full order rates beyond required_qty / previous_price.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'services' => 'App\\Services\\Purchasing\\PurchaseQuotationService',
                    'models' => ['App\\Models\\PurchaseQuotation', 'App\\Models\\PurchaseQuotationItem'],
                ],
            ],
        ],
    ],

    'purchases' => [
        'title' => 'Purchases',
        'summary' => 'Goods receipts (PUR-#####): supplier AP on suppliers.opening_balance, stock on partial/received delivery, optional channel pricing.',
        'meta' => [
            'routes' => ['/admin/purchasing/purchases'],
            'permissions' => ['purchasing.view', 'purchasing.create', 'purchasing.edit', 'purchasing.delete'],
            'related' => ['re-order-center', 'supplier-ledger', 'stock-report'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Primary inbound inventory document. List with search, delivery/payment/dispute filters, view/edit/delete, and print.',
                    'There is no separate Post button — saving with delivery_status partial or received applies stock; pending delivery does not.',
                ],
                'screenshot_placeholder' => true,
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'Header & line fields',
                'fields' => [
                    ['name' => 'purchase_number', 'label' => 'Purchase number', 'type' => 'string', 'required' => false, 'rules' => 'Auto PUR-#####'],
                    ['name' => 'supplier_id', 'label' => 'Supplier', 'type' => 'relation', 'required' => true, 'rules' => 'Active; shows opening_balance'],
                    ['name' => 'supplier_balance_snapshot', 'label' => 'Balance snapshot', 'type' => 'decimal', 'required' => false, 'rules' => 'Copied when supplier selected on create'],
                    ['name' => 'purchase_date', 'label' => 'Purchase date', 'type' => 'datetime', 'required' => true, 'rules' => 'Defaults now; UI display-only (no date picker)'],
                    ['name' => 'locale', 'label' => 'Locale', 'type' => 'enum', 'required' => true, 'rules' => 'en|ur'],
                    ['name' => 'purchaser_id', 'label' => 'Purchaser', 'type' => 'relation', 'required' => false, 'rules' => 'User'],
                    ['name' => 'vendor_bill_path', 'label' => 'Vendor bill', 'type' => 'file', 'required' => false, 'rules' => 'public disk purchases/vendor-bills'],
                    ['name' => 'delivery_status', 'label' => 'Delivery', 'type' => 'enum', 'required' => true, 'rules' => 'pending|partial|received'],
                    ['name' => 'payment_status', 'label' => 'Payment', 'type' => 'enum', 'required' => true, 'rules' => 'pending|partial|paid; paid forces amount_paid = total'],
                    ['name' => 'dispute_status', 'label' => 'Dispute', 'type' => 'enum', 'required' => true, 'rules' => 'ok|disputed'],
                    ['name' => 'amount_paid', 'label' => 'Amount paid', 'type' => 'decimal', 'required' => false, 'rules' => 'balance_due = max(0, total − paid)'],
                    ['name' => 'items.*.purchase_qty', 'label' => 'Purchase qty', 'type' => 'decimal', 'required' => true, 'rules' => 'Stock uses full qty for partial and received'],
                    ['name' => 'items.*.current_rate', 'label' => 'Current rate', 'type' => 'decimal', 'required' => false, 'rules' => ''],
                    ['name' => 'items.*.landing_cost', 'label' => 'Landing cost', 'type' => 'decimal', 'required' => false, 'rules' => 'cost_price = rate + landing'],
                    ['name' => 'items.*.sale_price', 'label' => 'Sale price', 'type' => 'decimal', 'required' => false, 'rules' => 'Prefill product retail'],
                    ['name' => 'items.*.line_total', 'label' => 'Line total', 'type' => 'decimal', 'required' => false, 'rules' => 'qty × rate'],
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Save pipeline',
                'steps' => [
                    'Validate statuses and lines; assign PUR-##### if needed.',
                    'Persist header; replace all line items (delete/recreate).',
                    'applySupplierLedger — adjust suppliers.opening_balance by (total_amount − amount_paid); reverse/adjust on update/delete.',
                    'applyChannelPricing — if markup tiers exist, apply purchase price (and optional retail) to product.',
                    'If delivery partial/received: ReorderService::applyPurchaseStock adds each line purchase_qty to products.stock_qty, then fulfillQueueForProducts for those IDs.',
                    'Print via admin.purchases.print after save.',
                ],
            ],
            [
                'id' => 'rules',
                'type' => 'rules',
                'heading' => 'Rules',
                'list' => [
                    'Delivery pending → no stock change on create/update.',
                    'partial and received both add full purchase_qty (no separate received-qty column).',
                    'Payment status is not auto-derived from amount_paid (except paid forcing full pay).',
                    'Delete reverses stock (if was partial/received) and supplier ledger, removes vendor bill file.',
                    'Buyer block is CompanyProfile::current() — not editable on the form.',
                ],
            ],
            [
                'id' => 'integrations',
                'type' => 'integrations',
                'heading' => 'Integrations',
                'list' => [
                    'ReorderService — stock apply/reverse/refresh; queue fulfillment; inventory:rebuild-stock CLI.',
                    'ChannelPricingEngine — optional product price update from current_rate.',
                    'Supplier ledger report — purchase totals and amount_paid.',
                    'Price Tags — can load lines from purchase invoice number.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Purchase date not editable in UI despite validated field.',
                    'AP is only suppliers.opening_balance — no purchase ledger table; not linked to Purchase Payment Summary.',
                    'amount_paid does not post Finance bank/cash wallets.',
                    'Create with delivery pending still posts supplier ledger for invoice − paid.',
                    'Optional inventory:rebuild-stock reconciles stock from purchases − sales + returns.',
                ],
            ],
            [
                'id' => 'acceptance',
                'type' => 'acceptance',
                'heading' => 'Acceptance',
                'acceptance' => [
                    'Create purchase with delivery received → product stock_qty increases by purchase_qty.',
                    'Same purchase with amount_paid < total → supplier opening_balance increases by balance due.',
                    'Pending reorder queue items for those products become fulfilled.',
                    'Edit delivery from received to pending → stock reversed on save.',
                    'Delete completed receipt → stock and supplier balance reversed.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
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
        'summary' => 'Printable payment summary sheets (PPS-#####) — document only; does not update supplier balance.',
        'meta' => [
            'routes' => ['/admin/purchasing/purchase-payment-summary'],
            'permissions' => ['purchasing.view', 'purchasing.create', 'purchasing.edit', 'purchasing.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Build a summary with up to five payment_sources (label + amount, secret-code amounts supported) and supplier lines (linked or manual) for print.',
                ],
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'Fields',
                'fields' => [
                    ['name' => 'summary_number', 'label' => 'Summary number', 'type' => 'string', 'required' => false, 'rules' => 'Auto PPS-#####'],
                    ['name' => 'paper_size', 'label' => 'Paper', 'type' => 'enum', 'required' => true, 'rules' => 'a4|receipt; a5 normalized to a4'],
                    ['name' => 'payment_sources', 'label' => 'Payment sources', 'type' => 'array', 'required' => false, 'rules' => 'Max 5 label+amount'],
                    ['name' => 'items.*.payment_amount', 'label' => 'Line payment', 'type' => 'decimal', 'required' => false, 'rules' => 'line_status ok|dispute'],
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Does not update suppliers.opening_balance or purchase amount_paid.',
                    'supplierRowsForNewForm() exists in service but is unused by Livewire.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'services' => 'App\\Services\\Purchasing\\PurchasePaymentSummaryService',
                    'models' => ['App\\Models\\PurchasePaymentSummary', 'App\\Models\\PurchasePaymentSummaryItem'],
                ],
            ],
        ],
    ],

    're-order-center' => [
        'title' => 'Purchase Re-Orders',
        'summary' => 'Reorder candidates and queue (ROQ-#####). Stock changes only when a matching purchase is received.',
        'meta' => [
            'routes' => ['/admin/purchasing/re-order-center'],
            'permissions' => ['purchasing.view', 'purchasing.create'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Tabs: Reorder (products where stock_qty < required_qty) and Queue (pending/stale batches). Send selected candidates to queue; print reorder or queue lists.',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Workflow',
                'steps' => [
                    'Filter candidates: available | in_queue | repeated | all.',
                    'Select rows; Send to queue (needs purchasing.create) → ReorderQueueBatch ROQ-#####.',
                    'When a purchase for those products is saved as partial/received, pending queue items are marked fulfilled.',
                ],
            ],
            [
                'id' => 'rules',
                'type' => 'rules',
                'heading' => 'Rules',
                'list' => [
                    'Sending to queue does not change stock.',
                    'STALE_DAYS = 7 for queue aging.',
                    'No Add button on this page (formKey null in PurchasingPageToolbar).',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'No UI to cancel or manually fulfill queue items except via purchase receipt.',
                    'Queue items are not included in overview search.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
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
        'summary' => 'Browse products, build label lines, print barcodes/QR via label profiles — no stock posting.',
        'meta' => [
            'routes' => ['/admin/purchasing/price-tags'],
            'permissions' => ['purchasing.view', 'purchasing.create'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Prepare and print price tags. Can load lines from a purchase invoice number. Mutations (add products/lines/load invoice) require purchasing.create.',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Workflow',
                'steps' => [
                    'Filter by readiness (e.g. missing retail/barcode).',
                    'Add products or import from purchase; print batch via admin.price-tags.print.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Cost shown may come from last purchase rate or override — verify before print.',
                    'Depends on Settings printing / label profiles.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'livewire' => 'App\\Livewire\\PriceTags\\ListPriceTags',
                    'services' => ['App\\Services\\Purchasing\\PriceTagService', 'App\\Services\\Printing\\LabelRenderService'],
                ],
            ],
        ],
    ],
];
