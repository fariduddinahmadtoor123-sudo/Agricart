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

    <p class="agricart-reports-shell__lead">
        @if ($isStockReport)
            {{ __('reports.list.stock_hint') }}
        @elseif ($isCustomerLedgerReport)
            {{ __('reports.list.customer_ledger_hint') }}
        @else
            {{ __('reports.list.shell_hint') }}
        @endif
    </p>

    <div class="agricart-categories__table-wrap">
        <table class="agricart-categories__table">
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        @if (in_array($column['value'], $visibleColumns, true))
                            <th>{{ $column['label'] }}</th>
                        @endif
                    @endforeach
                    <th>{{ __('reports.list.col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @if ($isLiveReport && $reportLoaded)
                    @forelse ($rows as $row)
                        <tr wire:key="report-row-{{ $reportKey }}-{{ $row['id'] }}">
                            @foreach ($columns as $column)
                                @if (in_array($column['value'], $visibleColumns, true))
                                    <td>
                                        @if ($column['value'] === 'stock_status')
                                            <span @class([
                                                'agricart-reports-shell__badge',
                                                'is-in' => ($row['stock_status'] ?? '') === 'in',
                                                'is-low' => ($row['stock_status'] ?? '') === 'low',
                                                'is-out' => ($row['stock_status'] ?? '') === 'out',
                                            ])>
                                                {{ $row['stock_status_label'] ?? '' }}
                                            </span>
                                        @elseif ($column['value'] === 'balance' && $isCustomerLedgerReport)
                                            <span @class([
                                                'agricart-reports-shell__badge',
                                                'is-out' => ($row['balance_type'] ?? '') === 'receivable',
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
                            <td>
                                @if ($isStockReport)
                                    <a href="{{ $row['catalog_url'] }}" class="agricart-categories__action">
                                        {{ __('reports.actions.open_catalog') }}
                                    </a>
                                @elseif ($isCustomerLedgerReport)
                                    <div class="agricart-reports-shell__row-actions">
                                        <button
                                            type="button"
                                            class="agricart-categories__action"
                                            wire:click="openCustomerLedger({{ (int) $row['id'] }})"
                                        >
                                            {{ __('reports.actions.view_ledger') }}
                                        </button>
                                        <a href="{{ $row['contact_url'] }}" class="agricart-categories__action">
                                            {{ __('reports.actions.open_contacts') }}
                                        </a>
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr wire:key="report-shell-empty-{{ $reportKey }}">
                            <td colspan="{{ count($visibleColumns) + 1 }}" class="agricart-categories__empty-cell">
                                {{ $isCustomerLedgerReport ? __('reports.list.empty_customer_ledger') : __('reports.list.empty_stock') }}
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

    @if ($isCustomerLedgerReport && $ledgerDetailOpen && is_array($ledgerDetail))
        <div class="agricart-staff-approvals__modal" wire:keydown.escape.window="closeCustomerLedger">
            <div class="agricart-staff-approvals__modal-backdrop" wire:click="closeCustomerLedger"></div>
            <div
                class="agricart-staff-approvals__modal-card agricart-reports-shell__ledger-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="customer-ledger-detail-title"
            >
                <div class="agricart-staff-approvals__modal-header">
                    <div>
                        <h3 id="customer-ledger-detail-title" class="agricart-staff-approvals__modal-title">
                            {{ __('reports.customer_ledger.detail_title') }}
                        </h3>
                        <p class="agricart-staff-approvals__modal-text">
                            {{ $ledgerDetail['customer']['name'] ?? '—' }}
                            · {{ $ledgerDetail['customer']['number'] ?? '—' }}
                            · {{ $ledgerDetail['customer']['phone'] ?? '—' }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="agricart-staff-approvals__close"
                        wire:click="closeCustomerLedger"
                        aria-label="{{ __('reports.actions.close_ledger') }}"
                    >
                        ×
                    </button>
                </div>

                <div class="agricart-reports-shell__ledger-summary" role="group" aria-label="{{ __('reports.customer_ledger.detail_summary_aria') }}">
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
                                <th>{{ __('reports.customer_ledger.col_date') }}</th>
                                <th>{{ __('reports.customer_ledger.col_type') }}</th>
                                <th>{{ __('reports.customer_ledger.col_reference') }}</th>
                                <th>{{ __('reports.customer_ledger.col_particulars') }}</th>
                                <th>{{ __('reports.customer_ledger.col_method') }}</th>
                                <th class="is-numeric">{{ __('reports.columns.debit') }}</th>
                                <th class="is-numeric">{{ __('reports.columns.credit') }}</th>
                                <th class="is-numeric">{{ __('reports.columns.balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($ledgerDetail['lines'] ?? []) as $line)
                                <tr wire:key="ledger-line-{{ $loop->index }}-{{ $line['type'] ?? 'row' }}-{{ $line['reference'] ?? $loop->index }}">
                                    <td>{{ $line['date'] ?? '—' }}</td>
                                    <td>{{ $line['type_label'] ?? '—' }}</td>
                                    <td>{{ $line['reference'] ?? '—' }}</td>
                                    <td>{{ $line['particulars'] ?? '—' }}</td>
                                    <td>{{ $line['method'] ?? '—' }}</td>
                                    <td class="is-numeric">{{ $line['debit'] ?? '0.00' }}</td>
                                    <td class="is-numeric">{{ $line['credit'] ?? '0.00' }}</td>
                                    <td class="is-numeric">{{ $line['balance'] ?? '0.00' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="agricart-categories__empty-cell">
                                        {{ __('reports.customer_ledger.empty_history') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="agricart-staff-approvals__modal-actions">
                    <button type="button" class="agricart-categories__action" wire:click="closeCustomerLedger">
                        {{ __('reports.actions.close_ledger') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
