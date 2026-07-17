<?php

return [
    'overview' => [
        'title' => 'Sales & POS — Overview',
        'summary' => 'Counter sales, quotations, returns, online orders, and dispatch queue.',
        'meta' => [
            'routes' => ['/admin/sales/overview'],
            'permissions' => ['sales.view'],
            'related' => ['catalog', 'contacts', 'store', 'finance', 'reports'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Sales covers POS invoices, sale quotations, returns, web orders from the storefront, and the paid-order dispatch queue.',
                    'Overview shows module stats and search via SalesOverviewService / SalesSearchService. Toolbar is search-only (no Add).',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Typical paths',
                'steps' => [
                    'POS: create sale on Sales & POS → complete or hold → print.',
                    'Online: customer checks out on storefront → admin Online Orders → verify bank proof if needed → Dispatch Queue.',
                    'Returns: create against a completed sale number → stock increments.',
                ],
            ],
            [
                'id' => 'integrations',
                'type' => 'integrations',
                'heading' => 'Connections',
                'list' => [
                    'Catalog — product search/scan and stock_qty.',
                    'Contacts — customers on tickets and ledgers.',
                    'Finance — bank receipts on completed bank_transfer sales and verified online bank proofs.',
                    'Reports — customer ledger, sales summary, P&L COGS from completed sales.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Livewire Pos\\Terminal exists but is unwired; production POS is Sales\\Form in the module form panel.',
                    'No card payment method — cash and bank_transfer only.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
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
        'summary' => 'Completed and held POS invoices: cart, cash/bank payment, stock on complete, bank ledger credit, print.',
        'meta' => [
            'routes' => ['/admin/sales/pos-sales'],
            'permissions' => ['sales.view', 'sales.create', 'sales.edit', 'sales.delete'],
            'related' => ['customer-ledger', 'sales-summary', 'profit-loss'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'List and manage sales. Add opens the shared form panel (formKey sales → Livewire Sales\\Form). Topbar quick POS may open with ?open=sale.',
                    'Product search/scan reuses PurchaseService helpers via SaleService. One line per product; QuantityPricingEngine (forPos: true) recalculates rows.',
                ],
                'screenshot_placeholder' => true,
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'Key fields',
                'fields' => [
                    ['name' => 'sale_number', 'label' => 'Sale number', 'type' => 'string', 'required' => false, 'rules' => 'Auto-assigned on save'],
                    ['name' => 'customer_id', 'label' => 'Customer', 'type' => 'relation', 'required' => false, 'rules' => 'Search/select/clear'],
                    ['name' => 'payment_method', 'label' => 'Payment method', 'type' => 'enum', 'required' => true, 'rules' => 'cash|bank_transfer only'],
                    ['name' => 'bank_account_id', 'label' => 'Bank account', 'type' => 'relation', 'required' => false, 'rules' => 'Required when bank_transfer and active banks exist'],
                    ['name' => 'payment_status', 'label' => 'Payment status', 'type' => 'enum', 'required' => true, 'rules' => 'paid|partial|unpaid; may derive from amount_paid'],
                    ['name' => 'amount_paid', 'label' => 'Amount paid', 'type' => 'decimal', 'required' => false, 'rules' => 'Balance = total − paid'],
                    ['name' => 'status', 'label' => 'Sale status', 'type' => 'enum', 'required' => true, 'rules' => 'held|completed (and related list filters)'],
                    ['name' => 'items.*.qty', 'label' => 'Quantity', 'type' => 'decimal', 'required' => true, 'rules' => '0 allowed on held; completed needs billable qty > 0'],
                    ['name' => 'items.*.unit_price', 'label' => 'Unit price', 'type' => 'decimal', 'required' => false, 'rules' => 'Manual price flag supported'],
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Workflows',
                'steps' => [
                    'Add lines via search or scan; set customer and payment.',
                    'Hold — SaleService::hold(); no stock movement.',
                    'Complete — adjustStock decrements Product.stock_qty; overselling allowed when config agricart.inventory.allow_overselling is true.',
                    'Bank transfer with amount_paid > 0 — BankAccountService::recordSaleReceiptIfApplicable credits the bank account.',
                    'Print — admin.sales.print (A4/receipt, en/ur).',
                ],
            ],
            [
                'id' => 'rules',
                'type' => 'rules',
                'heading' => 'Rules',
                'list' => [
                    'sales.create for add/complete/hold; sales.edit for held edits; sales.delete for list delete; sales.view for view/print.',
                    'Delete of a completed sale restores stock.',
                    'Credit exposure is balance on the sale — no separate AR table; customer ledger report aggregates completed sales.',
                ],
            ],
            [
                'id' => 'integrations',
                'type' => 'integrations',
                'heading' => 'Integrations',
                'list' => [
                    'SaleService, SalesBankAccountService, QuantityPricingEngine.',
                    'BankAccountService for sale receipts.',
                    'CustomerLedgerReportService / SalesSummaryReportService / ProfitLossReportService.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'App\\Livewire\\Pos\\Terminal is orphaned (not mounted); do not document it as the live UI.',
                    'No card payment type in SaleService::PAYMENT_METHODS.',
                    'No quick-add customer in Sales\\Form (only in unwired Terminal).',
                ],
            ],
            [
                'id' => 'acceptance',
                'type' => 'acceptance',
                'heading' => 'Acceptance',
                'acceptance' => [
                    'Complete cash sale → stock decreases; payment_status paid when fully paid.',
                    'Hold sale → no stock change until completed.',
                    'Complete bank_transfer with bank_account_id and amount_paid → bank transaction credit.',
                    'Delete completed sale → stock restored.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
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
        'summary' => 'Customer quotation documents with print — no stock, bank, or convert-to-sale.',
        'meta' => [
            'routes' => ['/admin/sales/sales-quotations'],
            'permissions' => ['sales.view', 'sales.create', 'sales.edit', 'sales.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'CRUD list + form panel (formKey sale-quotations). Locale filter; print via admin.sale-quotations.print.',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Workflow',
                'steps' => [
                    'Create quotation with customer and lines.',
                    'Save and optionally print.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'No convert quotation → POS sale action.',
                    'No stock or ledger effects.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'services' => 'App\\Services\\Sales\\SaleQuotationService',
                    'models' => ['App\\Models\\SaleQuotation', 'App\\Models\\SaleQuotationItem'],
                ],
            ],
        ],
    ],

    'sales-returns' => [
        'title' => 'Sale Returns',
        'summary' => 'Returns linked to a completed sale; stock increments; bank refund ledger not posted.',
        'meta' => [
            'routes' => ['/admin/sales/sales-returns'],
            'permissions' => ['sales.view', 'sales.create', 'sales.edit', 'sales.delete'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Create returns against a completed sale by number. Line qtys capped vs prior returns. Stock increases for return lines. Print via admin.sale-returns.print.',
                ],
            ],
            [
                'id' => 'fields',
                'type' => 'fields',
                'heading' => 'Fields',
                'fields' => [
                    ['name' => 'sale_id', 'label' => 'Original sale', 'type' => 'relation', 'required' => true, 'rules' => 'findSaleForReturn by number'],
                    ['name' => 'payment_method', 'label' => 'Refund method', 'type' => 'enum', 'required' => false, 'rules' => 'cash|bank_transfer UI mirrors POS'],
                    ['name' => 'bank_account_id', 'label' => 'Bank account', 'type' => 'relation', 'required' => false, 'rules' => 'Stored/validated; no BankAccountService debit'],
                    ['name' => 'refund_total', 'label' => 'Refund total', 'type' => 'decimal', 'required' => false, 'rules' => 'Feeds customer ledger credits'],
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'Unlike POS sales, returns do not post bank ledger refunds despite bank fields.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'services' => 'App\\Services\\Sales\\SaleReturnService',
                    'models' => ['App\\Models\\SaleReturn', 'App\\Models\\SaleReturnItem'],
                ],
            ],
        ],
    ],

    'online-orders' => [
        'title' => 'Online Sale Orders',
        'summary' => 'Admin list/edit of storefront WEB- orders; bank proof verify can post bank receipt and feed dispatch.',
        'meta' => [
            'routes' => ['/admin/sales/online-orders'],
            'permissions' => ['sales.view', 'sales.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Orders are created by storefront CheckoutController → OnlineSaleOrderService::createFromCheckout (WEB-xxxxx, typically pending/unpaid).',
                    'Admin has no Add button — view/edit in form panel: order/fulfillment/payment/assignee/suppliers; verify or reject bank proof.',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Workflow',
                'steps' => [
                    'Customer checks out online.',
                    'Admin updates status fields; verify bank proof → paid + recordOnlineOrderReceiptIfApplicable + dispatch queue eligibility.',
                    'Print order / packing slip routes as configured.',
                ],
            ],
            [
                'id' => 'rules',
                'type' => 'rules',
                'heading' => 'Rules',
                'list' => [
                    'Online orders do not deduct stock at order time.',
                    'Customer ledger includes non-cancelled online orders without sale_id.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'STATUS_CONVERTED and sale_id exist on the model but no UI/service sets conversion to a POS sale.',
                    'isLocked() related to conversion is unused without conversion flow.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
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
        'summary' => 'Operational queue for paid/partial online orders (not cancelled/converted); shared online-orders form.',
        'meta' => [
            'routes' => ['/admin/sales/order-dispatch'],
            'permissions' => ['sales.view', 'sales.edit'],
        ],
        'sections' => [
            [
                'id' => 'purpose',
                'type' => 'purpose',
                'heading' => 'Purpose',
                'paragraphs' => [
                    'Filtered list matching SalesOverviewService::dispatchQueueQuery. Assign suppliers, WhatsApp/mail links, fulfillment proofs via OnlineOrderDispatchService.',
                ],
            ],
            [
                'id' => 'workflows',
                'type' => 'workflows',
                'heading' => 'Workflow',
                'steps' => [
                    'Open queue item → same OnlineSaleOrders\\Form as online-orders.',
                    'Suggest/assign suppliers; upload supplier fulfillment proofs.',
                ],
            ],
            [
                'id' => 'rebuild_notes',
                'type' => 'rebuild_notes',
                'heading' => 'Rebuild notes',
                'list' => [
                    'No separate dispatch-only form; no convert to POS sale; no inventory allocation or shipment ledger.',
                ],
            ],
            [
                'id' => 'technical',
                'type' => 'technical',
                'heading' => 'Technical',
                'classes' => [
                    'livewire' => 'App\\Livewire\\OnlineSaleOrders\\ListOrderDispatch',
                    'services' => 'App\\Services\\Sales\\OnlineOrderDispatchService',
                ],
            ],
        ],
    ],
];
