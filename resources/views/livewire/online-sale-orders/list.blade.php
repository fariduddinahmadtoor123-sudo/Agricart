<div class="agricart-categories agricart-purchases">
    @if ($activeFilters !== [])
        <div class="agricart-list-filters">
            @foreach ($activeFilters as $filter)
                <button
                    type="button"
                    class="agricart-list-filters__chip"
                    wire:click="removeFilter('{{ $filter['field'] }}')"
                    wire:key="online-order-filter-{{ $filter['field'] }}"
                >
                    <span>{{ $filter['label'] }}</span>
                    <span aria-hidden="true">×</span>
                </button>
            @endforeach

            @if ($hasListConstraints)
                <button type="button" class="agricart-list-filters__clear" wire:click="clearFilters">
                    {{ __('online_sale_orders.list.clear_filters') }}
                </button>
            @endif
        </div>
    @endif

    @if ($orders->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ $hasListConstraints ? __('online_sale_orders.list.no_results') : __('online_sale_orders.list.empty') }}</p>
            @unless ($hasListConstraints)
                <p class="agricart-categories__empty-hint">{{ __('online_sale_orders.list.empty_hint') }}</p>
            @endunless
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('order_number', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_number') }}</th>
                        @endif
                        @if (in_array('customer', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_customer') }}</th>
                        @endif
                        @if (in_array('total_amount', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_total') }}</th>
                        @endif
                        @if (in_array('order_status', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_order_status') }}</th>
                        @endif
                        @if (in_array('payment_status', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_payment_status') }}</th>
                        @endif
                        @if (in_array('customer_payment_method', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_customer_payment') }}</th>
                        @endif
                        @if (in_array('order_date', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_order_date') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_created') }}</th>
                        @endif
                        <th>{{ __('online_sale_orders.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr wire:key="online-order-{{ $order->id }}">
                            @if (in_array('order_number', $visibleColumns, true))
                                <td><code>{{ $order->order_number }}</code></td>
                            @endif
                            @if (in_array('customer', $visibleColumns, true))
                                <td>{{ $order->customer?->displayLabel() ?? '—' }}</td>
                            @endif
                            @if (in_array('total_amount', $visibleColumns, true))
                                <td>{{ number_format((float) $order->total_amount, 2) }}</td>
                            @endif
                            @if (in_array('order_status', $visibleColumns, true))
                                <td>{{ __('online_sale_orders.order_statuses.' . $order->order_status) }}</td>
                            @endif
                            @if (in_array('payment_status', $visibleColumns, true))
                                <td>{{ __('pos.payment_statuses.' . $order->payment_status) }}</td>
                            @endif
                            @if (in_array('customer_payment_method', $visibleColumns, true))
                                <td>
                                    @if (filled($order->customer_payment_method))
                                        {{ __('pos.payment_methods.' . $order->customer_payment_method) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                            @if (in_array('order_date', $visibleColumns, true))
                                <td>{{ $order->order_date?->format('Y-m-d') }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $order->id }})">
                                    {{ __('online_sale_orders.actions.view_short') }}
                                </button>
                                <button type="button" class="agricart-categories__action" wire:click="printOrder({{ $order->id }})">{{ __('online_sale_orders.actions.print_short') }}</button>
                                @if ($canEdit && ! $order->isLocked())
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $order->id }})">
                                        {{ __('online_sale_orders.actions.edit_short') }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $orders->links() }}
        </div>
    @endif
</div>

@script
<script>
    Livewire.on('online-sale-orders-open-print', (payload) => {
        const url = payload?.url ?? payload?.[0]?.url;

        if (url) {
            window.open(url, '_blank');
        }
    });
</script>
@endscript
