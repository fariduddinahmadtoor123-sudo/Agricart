<div class="agricart-categories agricart-purchases">
    @if ($activeFilters !== [])
        <div class="agricart-list-filters">
            @foreach ($activeFilters as $filter)
                <button
                    type="button"
                    class="agricart-list-filters__chip"
                    wire:click="removeFilter('{{ $filter['field'] }}')"
                    wire:key="sale-return-filter-{{ $filter['field'] }}"
                >
                    <span>{{ $filter['label'] }}</span>
                    <span aria-hidden="true">×</span>
                </button>
            @endforeach

            @if ($hasListConstraints)
                <button type="button" class="agricart-list-filters__clear" wire:click="clearFilters">
                    {{ __('sale_returns.list.clear_filters') }}
                </button>
            @endif
        </div>
    @endif

    @if ($returns->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ $hasListConstraints ? __('sale_returns.list.no_results') : __('sale_returns.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('return_number', $visibleColumns, true))
                            <th>{{ __('sale_returns.list.col_number') }}</th>
                        @endif
                        @if (in_array('sale_number', $visibleColumns, true))
                            <th>{{ __('sale_returns.list.col_sale_number') }}</th>
                        @endif
                        @if (in_array('customer', $visibleColumns, true))
                            <th>{{ __('sale_returns.list.col_customer') }}</th>
                        @endif
                        @if (in_array('refund_total', $visibleColumns, true))
                            <th>{{ __('sale_returns.list.col_refund_total') }}</th>
                        @endif
                        @if (in_array('payment_status', $visibleColumns, true))
                            <th>{{ __('sale_returns.list.col_payment_status') }}</th>
                        @endif
                        @if (in_array('payment_method', $visibleColumns, true))
                            <th>{{ __('sale_returns.list.col_payment_method') }}</th>
                        @endif
                        @if (in_array('return_date', $visibleColumns, true))
                            <th>{{ __('sale_returns.list.col_return_date') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('sale_returns.list.col_created') }}</th>
                        @endif
                        <th>{{ __('sale_returns.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($returns as $return)
                        <tr wire:key="sale-return-{{ $return->id }}">
                            @if (in_array('return_number', $visibleColumns, true))
                                <td><code>{{ $return->return_number }}</code></td>
                            @endif
                            @if (in_array('sale_number', $visibleColumns, true))
                                <td><code>{{ $return->sale?->sale_number ?? '—' }}</code></td>
                            @endif
                            @if (in_array('customer', $visibleColumns, true))
                                <td>{{ $return->customer?->displayLabel() ?? '—' }}</td>
                            @endif
                            @if (in_array('refund_total', $visibleColumns, true))
                                <td>{{ number_format((float) $return->refund_total, 2) }}</td>
                            @endif
                            @if (in_array('payment_status', $visibleColumns, true))
                                <td>{{ __('pos.payment_statuses.' . $return->payment_status) }}</td>
                            @endif
                            @if (in_array('payment_method', $visibleColumns, true))
                                <td>{{ __('pos.payment_methods.' . $return->payment_method) }}</td>
                            @endif
                            @if (in_array('return_date', $visibleColumns, true))
                                <td>{{ $return->return_date?->format('M j, Y') }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $return->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $return->id }})">{{ __('sale_returns.actions.view_short') }}</button>
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $return->id }})">{{ __('sale_returns.actions.edit_short') }}</button>
                                @endif
                                <button type="button" class="agricart-categories__action" wire:click="printReturn({{ $return->id }})">{{ __('sale_returns.actions.print_short') }}</button>
                                @if ($canDelete)
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="deleteReturn({{ $return->id }})" wire:confirm="{{ __('sale_returns.actions.delete_confirm') }}">{{ __('sale_returns.actions.delete_short') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $returns->links() }}
        </div>
    @endif
</div>

@script
<script>
    Livewire.on('sale-returns-open-print', (payload) => {
        const url = payload?.url ?? payload?.[0]?.url;

        if (url) {
            window.open(url, '_blank');
        }
    });
</script>
@endscript
