<div class="agricart-categories agricart-reports-shell">
    @if ($statusMessage)
        <div @class([
            'agricart-reports-shell__status',
            'is-info' => $statusType === 'info',
            'is-success' => $statusType === 'success',
        ]) role="status">
            {{ $statusMessage }}
        </div>
    @endif

    @if ($activeFilters !== [])
        <div class="agricart-list-filters">
            @foreach ($activeFilters as $filter)
                <button
                    type="button"
                    class="agricart-list-filters__chip"
                    wire:click="removeFilter('{{ $filter['field'] }}')"
                    wire:key="report-filter-{{ $reportKey }}-{{ $filter['field'] }}"
                >
                    <span>{{ $filter['label'] }}</span>
                    <span aria-hidden="true">×</span>
                </button>
            @endforeach

            @if ($hasListConstraints)
                <button type="button" class="agricart-list-filters__clear" wire:click="clearFilters">
                    {{ __('reports.list.clear_filters') }}
                </button>
            @endif
        </div>
    @endif

    @if ($isStockReport && $reportLoaded)
        <div class="agricart-reports-shell__kpis" role="group" aria-label="{{ __('reports.stock.kpis_aria') }}">
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.stock.kpi_total_skus') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['total_skus'] ?? 0 }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.stock.kpi_in_stock') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['in_stock'] ?? 0 }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi is-warning">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.stock.kpi_low_stock') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['low_stock'] ?? 0 }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi is-danger">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.stock.kpi_out_of_stock') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['out_of_stock'] ?? 0 }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.stock.kpi_on_reorder') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['on_reorder'] ?? 0 }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.stock.kpi_total_on_hand') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['total_on_hand'] ?? 0 }}</strong>
            </div>
        </div>
    @endif

    @if ($isCustomerLedgerReport && $reportLoaded)
        <div class="agricart-reports-shell__kpis" role="group" aria-label="{{ __('reports.customer_ledger.kpis_aria') }}">
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.customer_ledger.kpi_total_customers') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['total_customers'] ?? 0 }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.customer_ledger.kpi_total_debit') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['total_debit'] ?? '0.00' }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.customer_ledger.kpi_total_credit') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['total_credit'] ?? '0.00' }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi is-danger">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.customer_ledger.kpi_receivable') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['receivable'] ?? '0.00' }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi is-warning">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.customer_ledger.kpi_advance') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['advance'] ?? '0.00' }}</strong>
            </div>
        </div>
    @endif

    @if ($isSupplierLedgerReport && $reportLoaded)
        <div class="agricart-reports-shell__kpis" role="group" aria-label="{{ __('reports.supplier_ledger.kpis_aria') }}">
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.supplier_ledger.kpi_total_suppliers') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['total_suppliers'] ?? 0 }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.supplier_ledger.kpi_total_debit') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['total_debit'] ?? '0.00' }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.supplier_ledger.kpi_total_credit') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['total_credit'] ?? '0.00' }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi is-danger">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.supplier_ledger.kpi_payable') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['payable'] ?? '0.00' }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi is-warning">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.supplier_ledger.kpi_advance') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['advance'] ?? '0.00' }}</strong>
            </div>
        </div>
    @endif

    @if ($isSalesSummaryReport && $reportLoaded)
        <div class="agricart-reports-shell__kpis" role="group" aria-label="{{ __('reports.sales.kpis_aria') }}">
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.sales.kpi_invoices') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['invoices'] ?? 0 }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.sales.kpi_gross_sales') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['gross_sales'] ?? '0.00' }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi is-warning">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.sales.kpi_returns') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['returns'] ?? '0.00' }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.sales.kpi_net_sales') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['net_sales'] ?? '0.00' }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.sales.kpi_avg_ticket') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['avg_ticket'] ?? '0.00' }}</strong>
            </div>
        </div>
    @endif

    @if ($isProfitLossReport && $reportLoaded)
        <div class="agricart-reports-shell__kpis" role="group" aria-label="{{ __('reports.profit.kpis_aria') }}">
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.profit.kpi_invoices') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['invoices'] ?? 0 }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.profit.kpi_net_sales') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['net_sales'] ?? '0.00' }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi is-warning">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.profit.kpi_cogs') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['cogs'] ?? '0.00' }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.profit.kpi_gross_profit') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['gross_profit'] ?? '0.00' }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi is-in">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.profit.kpi_with_profit') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['profit_count'] ?? 0 }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi is-danger">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.profit.kpi_with_loss') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['loss_count'] ?? 0 }}</strong>
            </div>
        </div>
    @endif

    @if ($isExpenseReport && $reportLoaded)
        <div class="agricart-reports-shell__kpis" role="group" aria-label="{{ __('reports.expense.kpis_aria') }}">
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.expense.kpi_payments') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['payments'] ?? 0 }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.expense.kpi_total') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['total_amount'] ?? '0.00' }}</strong>
            </div>
        </div>
    @endif

    @if ($isCashReport && $reportLoaded)
        <div class="agricart-reports-shell__kpis" role="group" aria-label="{{ __('reports.cash.kpis_aria') }}">
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.cash.kpi_entries') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['entries'] ?? 0 }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi is-in">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.cash.kpi_in') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['cash_in'] ?? '0.00' }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi is-danger">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.cash.kpi_out') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['cash_out'] ?? '0.00' }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.cash.kpi_balance') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['balance'] ?? '0.00' }}</strong>
            </div>
        </div>
    @endif

    @if (($isSeasonalSalesReport || $isSeasonalPurchasesReport) && $reportLoaded)
        <div class="agricart-reports-shell__kpis" role="group" aria-label="{{ __('reports.seasonal.kpis_aria') }}">
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">
                    {{ $isSeasonalSalesReport ? __('reports.seasonal.kpi_products') : __('reports.seasonal.kpi_lines') }}
                </span>
                <strong class="agricart-reports-shell__kpi-value">
                    {{ $isSeasonalSalesReport ? ($kpis['products'] ?? 0) : ($kpis['lines'] ?? 0) }}
                </strong>
            </div>
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">{{ __('reports.seasonal.kpi_quantity') }}</span>
                <strong class="agricart-reports-shell__kpi-value">{{ $kpis['quantity'] ?? '0.000' }}</strong>
            </div>
            <div class="agricart-reports-shell__kpi">
                <span class="agricart-reports-shell__kpi-label">
                    {{ $isSeasonalSalesReport ? __('reports.seasonal.kpi_net_sales') : __('reports.seasonal.kpi_purchase_value') }}
                </span>
                <strong class="agricart-reports-shell__kpi-value">
                    {{ $isSeasonalSalesReport ? ($kpis['net_sales'] ?? '0.00') : ($kpis['purchase_value'] ?? '0.00') }}
                </strong>
            </div>
        </div>
    @endif

    <p class="agricart-reports-shell__lead">
        @if ($isStockReport)
            {{ __('reports.list.stock_hint') }}
        @elseif ($isCustomerLedgerReport)
            {{ __('reports.list.customer_ledger_hint') }}
        @elseif ($isSupplierLedgerReport)
            {{ __('reports.list.supplier_ledger_hint') }}
        @elseif ($isSalesSummaryReport)
            {{ __('reports.list.sales_summary_hint') }}
        @elseif ($isProfitLossReport)
            {{ __('reports.list.profit_loss_hint') }}
        @elseif ($isExpenseReport)
            {{ __('reports.list.expense_hint') }}
        @elseif ($isCashReport)
            {{ __('reports.list.cash_hint') }}
        @elseif ($isSeasonalSalesReport)
            {{ __('reports.list.seasonal_sales_hint') }}
        @elseif ($isSeasonalPurchasesReport)
            {{ __('reports.list.seasonal_purchases_hint') }}
        @else
            {{ __('reports.list.shell_hint') }}
        @endif
    </p>

    <div class="agricart-categories__table-wrap">
        <table @class([
            'agricart-categories__table',
            'agricart-reports-shell__list-table' => $isPartyLedgerReport || $isStockReport || $isSalesSummaryReport || $isProfitLossReport || $isExpenseReport || $isCashReport || $isSeasonalSalesReport || $isSeasonalPurchasesReport,
        ])>
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        @if (in_array($column['value'], $visibleColumns, true))
                            <th @class([
                                'is-numeric' => ($isPartyLedgerReport && in_array($column['value'], ['opening', 'debit', 'credit', 'balance'], true))
                                    || ($isSalesSummaryReport && in_array($column['value'], ['invoices', 'gross_sales', 'returns', 'net_sales', 'avg_ticket'], true))
                                    || ($isProfitLossReport && in_array($column['value'], ['gross_sales', 'returns', 'net_sales', 'cogs', 'profit', 'margin'], true))
                                    || ($isExpenseReport && $column['value'] === 'amount')
                                    || ($isCashReport && in_array($column['value'], ['cash_in', 'cash_out', 'balance'], true))
                                    || ($isSeasonalSalesReport && in_array($column['value'], ['qty_sold', 'net_sales', 'avg_price'], true))
                                    || ($isSeasonalPurchasesReport && in_array($column['value'], ['qty_purchased', 'purchase_value'], true)),
                            ])>
                                {{ $column['label'] }}
                            </th>
                        @endif
                    @endforeach
                    <th class="is-actions">{{ __('reports.list.col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @if ($isLiveReport && $reportLoaded)
                    @forelse ($rows as $row)
                        <tr
                            wire:key="report-row-{{ $reportKey }}-{{ $row['id'] }}"
                            class="agricart-categories__row"
                        >
                            @foreach ($columns as $column)
                                @if (in_array($column['value'], $visibleColumns, true))
                                    <td @class([
                                        'is-numeric' => ($isPartyLedgerReport && in_array($column['value'], ['opening', 'debit', 'credit', 'balance'], true))
                                            || ($isSalesSummaryReport && in_array($column['value'], ['invoices', 'gross_sales', 'returns', 'net_sales', 'avg_ticket'], true))
                                            || ($isProfitLossReport && in_array($column['value'], ['gross_sales', 'returns', 'net_sales', 'cogs', 'profit', 'margin'], true))
                                            || ($isExpenseReport && $column['value'] === 'amount')
                                            || ($isCashReport && in_array($column['value'], ['cash_in', 'cash_out', 'balance'], true))
                                            || ($isSeasonalSalesReport && in_array($column['value'], ['qty_sold', 'net_sales', 'avg_price'], true))
                                            || ($isSeasonalPurchasesReport && in_array($column['value'], ['qty_purchased', 'purchase_value'], true)),
                                        'is-customer' => ($isPartyLedgerReport && in_array($column['value'], ['customer', 'supplier'], true))
                                            || ($isProfitLossReport && $column['value'] === 'customer'),
                                    ])>
                                        @if ($column['value'] === 'stock_status')
                                            <span @class([
                                                'agricart-reports-shell__badge',
                                                'is-in' => ($row['stock_status'] ?? '') === 'in',
                                                'is-low' => ($row['stock_status'] ?? '') === 'low',
                                                'is-out' => ($row['stock_status'] ?? '') === 'out',
                                            ])>
                                                {{ $row['stock_status_label'] ?? '' }}
                                            </span>
                                        @elseif ($column['value'] === 'result' && $isProfitLossReport)
                                            <span @class([
                                                'agricart-reports-shell__badge',
                                                'is-in' => ($row['result'] ?? '') === 'profit',
                                                'is-out' => ($row['result'] ?? '') === 'loss',
                                                'is-low' => ($row['result'] ?? '') === 'even',
                                            ])>
                                                {{ $row['result_label'] ?? '' }}
                                            </span>
                                        @elseif ($column['value'] === 'profit' && $isProfitLossReport)
                                            <span @class([
                                                'agricart-reports-shell__signed',
                                                'is-profit' => ($row['result'] ?? '') === 'profit',
                                                'is-loss' => ($row['result'] ?? '') === 'loss',
                                            ])>
                                                {{ $row['profit'] ?? '0.00' }}
                                            </span>
                                        @elseif ($column['value'] === 'customer' && $isCustomerLedgerReport)
                                            <span class="agricart-reports-shell__party-name">{{ $row['customer'] ?? '—' }}</span>
                                            @if (filled($row['phone'] ?? null) && ($row['phone'] ?? '') !== '—' && ! in_array('phone', $visibleColumns, true))
                                                <span class="agricart-reports-shell__party-meta">{{ $row['phone'] }}</span>
                                            @endif
                                        @elseif ($column['value'] === 'supplier' && $isSupplierLedgerReport)
                                            <span class="agricart-reports-shell__party-name">{{ $row['supplier'] ?? '—' }}</span>
                                            @if (filled($row['phone'] ?? null) && ($row['phone'] ?? '') !== '—' && ! in_array('phone', $visibleColumns, true))
                                                <span class="agricart-reports-shell__party-meta">{{ $row['phone'] }}</span>
                                            @endif
                                        @elseif ($column['value'] === 'balance' && $isPartyLedgerReport)
                                            <span @class([
                                                'agricart-reports-shell__badge',
                                                'is-out' => in_array($row['balance_type'] ?? '', ['receivable', 'payable'], true),
                                                'is-low' => ($row['balance_type'] ?? '') === 'advance',
                                                'is-in' => ($row['balance_type'] ?? '') === 'settled',
                                            ])>
                                                {{ $row['balance'] ?? '0.00' }}
                                            </span>
                                        @else
                                            {{ $row[$column['value']] ?? '—' }}
                                        @endif
                                    </td>
                                @endif
                            @endforeach
                            <td class="agricart-categories__actions">
                                @if ($isStockReport)
                                    <a href="{{ $row['catalog_url'] }}" class="agricart-categories__action">
                                        {{ __('reports.actions.open_catalog') }}
                                    </a>
                                @elseif ($isPartyLedgerReport)
                                    <button
                                        type="button"
                                        class="agricart-categories__action"
                                        wire:click="openLedgerDetail({{ (int) $row['id'] }})"
                                    >
                                        {{ __('reports.actions.view_short') }}
                                    </button>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr wire:key="report-shell-empty-{{ $reportKey }}">
                            <td colspan="{{ count($visibleColumns) + 1 }}" class="agricart-categories__empty-cell">
                                @if ($isProfitLossReport)
                                    {{ __('reports.list.empty_profit_loss') }}
                                @elseif ($isSalesSummaryReport)
                                    {{ __('reports.list.empty_sales_summary') }}
                                @elseif ($isExpenseReport)
                                    {{ __('reports.list.empty_expense') }}
                                @elseif ($isCashReport)
                                    {{ __('reports.list.empty_cash') }}
                                @elseif ($isSeasonalSalesReport)
                                    {{ __('reports.list.empty_seasonal_sales') }}
                                @elseif ($isSeasonalPurchasesReport)
                                    {{ __('reports.list.empty_seasonal_purchases') }}
                                @elseif ($isSupplierLedgerReport)
                                    {{ __('reports.list.empty_supplier_ledger') }}
                                @elseif ($isCustomerLedgerReport)
                                    {{ __('reports.list.empty_customer_ledger') }}
                                @else
                                    {{ __('reports.list.empty_stock') }}
                                @endif
                            </td>
                        </tr>
                    @endforelse
                @else
                    <tr wire:key="report-shell-empty-{{ $reportKey }}">
                        <td colspan="{{ count($visibleColumns) + 1 }}" class="agricart-categories__empty-cell">
                            {{ __('reports.list.empty_preview') }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="agricart-reports-shell__footer">
        <button type="button" class="agricart-categories__action agricart-reports-shell__view-btn" wire:click="viewReport">
            {{ __('reports.actions.view_report') }}
        </button>
        @if ($isLiveReport)
            <button type="button" class="agricart-categories__action" wire:click="exportCsv">
                {{ __('reports.actions.export') }}
            </button>
        @endif
    </div>

    @if ($isPartyLedgerReport && $ledgerDetailOpen && is_array($ledgerDetail))
        @php
            $ledgerLang = $isSupplierLedgerReport ? 'reports.supplier_ledger' : 'reports.customer_ledger';
            $ledgerParty = $ledgerDetail['party'] ?? $ledgerDetail['customer'] ?? [];
        @endphp
        <div class="agricart-staff-approvals__modal agricart-reports-shell__ledger-overlay" wire:keydown.escape.window="closeLedgerDetail">
            <div class="agricart-staff-approvals__modal-backdrop" wire:click="closeLedgerDetail"></div>
            <div
                class="agricart-staff-approvals__modal-card agricart-reports-shell__ledger-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="party-ledger-detail-title"
            >
                <div class="agricart-staff-approvals__modal-header">
                    <div class="agricart-reports-shell__ledger-heading">
                        <h3 id="party-ledger-detail-title" class="agricart-staff-approvals__modal-title">
                            {{ __($ledgerLang.'.detail_title') }}
                        </h3>
                        <p class="agricart-staff-approvals__modal-text agricart-reports-shell__ledger-party">
                            <strong>{{ $ledgerParty['name'] ?? '—' }}</strong>
                            <span>{{ $ledgerParty['number'] ?? '—' }}</span>
                            <span>{{ $ledgerParty['phone'] ?? '—' }}</span>
                        </p>
                    </div>
                    <button
                        type="button"
                        class="agricart-staff-approvals__close"
                        wire:click="closeLedgerDetail"
                        aria-label="{{ __('reports.actions.close_ledger') }}"
                    >
                        ×
                    </button>
                </div>

                <div class="agricart-reports-shell__ledger-summary" role="group" aria-label="{{ __($ledgerLang.'.detail_summary_aria') }}">
                    <div class="agricart-reports-shell__ledger-summary-item">
                        <span class="agricart-staff-approvals__detail-label">{{ __('reports.columns.opening') }}</span>
                        <strong>{{ $ledgerDetail['summary']['opening'] ?? '0.00' }}</strong>
                    </div>
                    <div class="agricart-reports-shell__ledger-summary-item">
                        <span class="agricart-staff-approvals__detail-label">{{ __('reports.columns.debit') }}</span>
                        <strong>{{ $ledgerDetail['summary']['debit'] ?? '0.00' }}</strong>
                    </div>
                    <div class="agricart-reports-shell__ledger-summary-item">
                        <span class="agricart-staff-approvals__detail-label">{{ __('reports.columns.credit') }}</span>
                        <strong>{{ $ledgerDetail['summary']['credit'] ?? '0.00' }}</strong>
                    </div>
                    <div class="agricart-reports-shell__ledger-summary-item">
                        <span class="agricart-staff-approvals__detail-label">{{ __('reports.columns.balance') }}</span>
                        <strong>{{ $ledgerDetail['summary']['balance'] ?? '0.00' }}</strong>
                    </div>
                </div>

                <div class="agricart-reports-shell__ledger-table-wrap">
                    <table class="agricart-categories__table agricart-reports-shell__ledger-table">
                        <thead>
                            <tr>
                                <th class="col-date">{{ __($ledgerLang.'.col_date') }}</th>
                                <th class="col-particulars">{{ __($ledgerLang.'.col_particulars') }}</th>
                                <th class="is-numeric col-money">{{ __('reports.columns.debit') }}</th>
                                <th class="is-numeric col-money">{{ __('reports.columns.credit') }}</th>
                                <th class="is-numeric col-money">{{ __('reports.columns.balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($ledgerDetail['lines'] ?? []) as $line)
                                <tr wire:key="ledger-line-{{ $loop->index }}-{{ $line['type'] ?? 'row' }}-{{ $line['reference'] ?? $loop->index }}">
                                    <td class="col-date">{{ $line['date'] ?? '—' }}</td>
                                    <td class="col-particulars">
                                        <span class="agricart-reports-shell__ledger-type">{{ $line['type_label'] ?? '—' }}</span>
                                        @if (($line['reference'] ?? '—') !== '—')
                                            <span class="agricart-reports-shell__ledger-ref">{{ $line['reference'] }}</span>
                                        @endif
                                        <span class="agricart-reports-shell__ledger-desc">{{ $line['particulars'] ?? '—' }}</span>
                                        @if (($line['method'] ?? '—') !== '—' && ($line['type'] ?? '') !== 'opening')
                                            <span class="agricart-reports-shell__ledger-method">{{ $line['method'] }}</span>
                                        @endif
                                    </td>
                                    <td class="is-numeric col-money">{{ $line['debit'] ?? '0.00' }}</td>
                                    <td class="is-numeric col-money">{{ $line['credit'] ?? '0.00' }}</td>
                                    <td class="is-numeric col-money">{{ $line['balance'] ?? '0.00' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="agricart-categories__empty-cell">
                                        {{ __($ledgerLang.'.empty_history') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="agricart-staff-approvals__modal-actions">
                    <button type="button" class="agricart-categories__action" wire:click="closeLedgerDetail">
                        {{ __('reports.actions.close_ledger') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
