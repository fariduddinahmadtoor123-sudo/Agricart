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

    <p class="agricart-reports-shell__lead">
        @if ($isStockReport)
            {{ __('reports.list.stock_hint') }}
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
                @if ($isStockReport && $reportLoaded)
                    @forelse ($rows as $row)
                        <tr wire:key="stock-row-{{ $row['id'] }}">
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
                                        @else
                                            {{ $row[$column['value']] ?? '—' }}
                                        @endif
                                    </td>
                                @endif
                            @endforeach
                            <td>
                                <a href="{{ $row['catalog_url'] }}" class="agricart-categories__action">
                                    {{ __('reports.actions.open_catalog') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr wire:key="report-shell-empty-{{ $reportKey }}">
                            <td colspan="{{ count($visibleColumns) + 1 }}" class="agricart-categories__empty-cell">
                                {{ __('reports.list.empty_stock') }}
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
        @if ($isStockReport)
            <button type="button" class="agricart-categories__action" wire:click="exportCsv">
                {{ __('reports.actions.export') }}
            </button>
        @endif
    </div>
</div>
