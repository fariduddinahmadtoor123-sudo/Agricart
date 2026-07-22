<div class="agricart-categories agricart-forwarded-orders">
    @if ($activeFilters !== [])
        <div class="agricart-list-filters">
            @foreach ($activeFilters as $filter)
                <button
                    type="button"
                    class="agricart-list-filters__chip"
                    wire:click="removeFilter('{{ $filter['field'] }}')"
                    wire:key="forwarded-filter-{{ $filter['field'] }}"
                >
                    <span>{{ $filter['label'] }}</span>
                    <span aria-hidden="true">×</span>
                </button>
            @endforeach

            @if ($hasListConstraints)
                <button type="button" class="agricart-list-filters__clear" wire:click="clearFilters">
                    {{ __('forwarded_orders.list.clear_filters') }}
                </button>
            @endif
        </div>
    @endif

    @if ($rows->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ $hasListConstraints ? __('forwarded_orders.list.no_results') : __('forwarded_orders.list.empty') }}</p>
            @unless ($hasListConstraints)
                <p class="agricart-categories__empty-hint">{{ __('forwarded_orders.list.empty_hint') }}</p>
            @endunless
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        <th>{{ __('forwarded_orders.list.col_order') }}</th>
                        <th>{{ __('forwarded_orders.list.col_invoice') }}</th>
                        <th>{{ __('forwarded_orders.list.col_from') }}</th>
                        <th>{{ __('forwarded_orders.list.col_to') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr wire:key="forwarded-row-{{ $row->id }}">
                            <td>
                                <code>{{ $row->order?->order_number ?? '—' }}</code>
                            </td>
                            <td>
                                @if (filled($row->order?->sale?->sale_number))
                                    <code>{{ $row->order->sale->sale_number }}</code>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $row->assignedBy?->name ?? '—' }}</td>
                            <td>{{ $row->user?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $rows->links() }}
        </div>
    @endif
</div>
