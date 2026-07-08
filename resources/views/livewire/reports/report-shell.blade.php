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

    <p class="agricart-reports-shell__lead">{{ __('reports.list.shell_hint') }}</p>

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
                <tr wire:key="report-shell-empty-{{ $reportKey }}">
                    <td colspan="{{ count($visibleColumns) + 1 }}" class="agricart-categories__empty-cell">
                        {{ __('reports.list.empty_preview') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="agricart-reports-shell__footer">
        <button type="button" class="agricart-categories__action agricart-reports-shell__view-btn" wire:click="viewReport">
            {{ __('reports.actions.view_report') }}
        </button>
    </div>
</div>
