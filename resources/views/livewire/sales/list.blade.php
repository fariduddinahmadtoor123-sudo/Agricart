<div class="agricart-categories agricart-purchases">
    @if ($activeFilters !== [])
        <div class="agricart-list-filters">
            @foreach ($activeFilters as $filter)
                <button
                    type="button"
                    class="agricart-list-filters__chip"
                    wire:click="removeFilter('{{ $filter['field'] }}')"
                    wire:key="sale-filter-{{ $filter['field'] }}"
                >
                    <span>{{ $filter['label'] }}</span>
                    <span aria-hidden="true">×</span>
                </button>
            @endforeach

            @if ($hasListConstraints)
                <button type="button" class="agricart-list-filters__clear" wire:click="clearFilters">
                    {{ __('pos.list.clear_filters') }}
                </button>
            @endif
        </div>
    @endif

    @if ($sales->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ $hasListConstraints ? __('pos.list.no_results') : __('pos.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('sale_number', $visibleColumns, true))
                            <th>{{ __('pos.list.col_number') }}</th>
                        @endif
                        @if (in_array('channel', $visibleColumns, true))
                            <th>{{ __('pos.list.col_channel') }}</th>
                        @endif
                        @if (in_array('customer', $visibleColumns, true))
                            <th>{{ __('pos.list.col_customer') }}</th>
                        @endif
                        @if (in_array('cashier', $visibleColumns, true))
                            <th>{{ __('pos.list.col_cashier') }}</th>
                        @endif
                        @if (in_array('total_amount', $visibleColumns, true))
                            <th>{{ __('pos.list.col_total') }}</th>
                        @endif
                        @if (in_array('invoice_status', $visibleColumns, true))
                            <th>{{ __('pos.list.col_invoice_status') }}</th>
                        @endif
                        @if (in_array('payment_status', $visibleColumns, true))
                            <th>{{ __('pos.list.col_payment_status') }}</th>
                        @endif
                        @if (in_array('payment_method', $visibleColumns, true))
                            <th>{{ __('pos.list.col_payment_method') }}</th>
                        @endif
                        @if (in_array('sale_date', $visibleColumns, true))
                            <th>{{ __('pos.list.col_sale_date') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('pos.list.col_created') }}</th>
                        @endif
                        <th>{{ __('pos.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sales as $sale)
                        <tr wire:key="sale-{{ $sale->id }}" @class(['agricart-categories__row', 'is-held' => $sale->isHeld()])>
                            @if (in_array('sale_number', $visibleColumns, true))
                                <td>
                                    <code>{{ $sale->sale_number }}</code>
                                    @if ($sale->onlineSaleOrder)
                                        <div><small><code>{{ $sale->onlineSaleOrder->order_number }}</code></small></div>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('channel', $visibleColumns, true))
                                <td>{{ $sale->isOnlineInvoice() ? __('pos.channels.online') : __('pos.channels.offline') }}</td>
                            @endif
                            @if (in_array('customer', $visibleColumns, true))
                                <td>{{ $sale->customer?->displayLabel() ?? '—' }}</td>
                            @endif
                            @if (in_array('cashier', $visibleColumns, true))
                                <td>{{ $sale->cashier?->name ?? '—' }}</td>
                            @endif
                            @if (in_array('total_amount', $visibleColumns, true))
                                <td>{{ number_format((float) $sale->total_amount, 2) }}</td>
                            @endif
                            @if (in_array('invoice_status', $visibleColumns, true))
                                <td>
                                    <span @class(['agricart-purchases__dispute', 'is-disputed' => $sale->isHeld()])>
                                        {{ __('pos.invoice_statuses.' . $sale->status) }}
                                    </span>
                                </td>
                            @endif
                            @if (in_array('payment_status', $visibleColumns, true))
                                <td>{{ __('pos.payment_statuses.' . $sale->payment_status) }}</td>
                            @endif
                            @if (in_array('payment_method', $visibleColumns, true))
                                <td>{{ __('pos.payment_methods.' . $sale->payment_method) }}</td>
                            @endif
                            @if (in_array('sale_date', $visibleColumns, true))
                                <td>{{ $sale->sale_date?->format('M j, Y') }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $sale->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $sale->id }})">{{ __('pos.actions.view_short') }}</button>
                                <button type="button" class="agricart-categories__action" wire:click="printSale({{ $sale->id }})">{{ __('pos.actions.print_short') }}</button>
                                @if ($canEdit && $sale->isHeld())
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $sale->id }})">{{ __('pos.actions.edit_short') }}</button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="deleteSale({{ $sale->id }})" wire:confirm="{{ __('pos.actions.delete_confirm') }}">{{ __('pos.actions.delete_short') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $sales->links() }}
        </div>
    @endif
</div>

@script
<script>
    Livewire.on('sales-open-print', (payload) => {
        const url = payload?.url ?? payload?.[0]?.url;

        if (url) {
            window.open(url, '_blank');
        }
    });
</script>
@endscript
