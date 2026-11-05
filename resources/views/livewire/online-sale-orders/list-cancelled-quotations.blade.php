<div class="agricart-categories agricart-purchases">
    @if ($activeFilters !== [])
        <div class="agricart-list-filters">
            @foreach ($activeFilters as $filter)
                <button
                    type="button"
                    class="agricart-list-filters__chip"
                    wire:click="removeFilter('{{ $filter['field'] }}')"
                    wire:key="cancelled-quotation-filter-{{ $filter['field'] }}"
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
            <p>{{ $hasListConstraints ? __('online_sale_orders.list.no_results') : __('online_sale_orders.list.cancelled_empty') }}</p>
            @unless ($hasListConstraints)
                <p class="agricart-categories__empty-hint">{{ __('online_sale_orders.list.cancelled_empty_hint') }}</p>
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
                        @if (in_array('cancellation_source', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_cancel_source') }}</th>
                        @endif
                        @if (in_array('cancelled_by', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_cancelled_by') }}</th>
                        @endif
                        @if (in_array('cancellation_reason', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_cancel_reason') }}</th>
                        @endif
                        @if (in_array('cancelled_at', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_cancelled_at') }}</th>
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
                        <tr wire:key="cancelled-quotation-{{ $order->id }}">
                            @if (in_array('order_number', $visibleColumns, true))
                                <td><code>{{ $order->order_number }}</code></td>
                            @endif
                            @if (in_array('customer', $visibleColumns, true))
                                <td>{{ $order->customer?->displayLabel() ?? '—' }}</td>
                            @endif
                            @if (in_array('total_amount', $visibleColumns, true))
                                <td>{{ number_format((float) $order->total_amount, 2) }}</td>
                            @endif
                            @if (in_array('cancellation_source', $visibleColumns, true))
                                <td>
                                    @if (filled($order->cancellation_source))
                                        {{ __('online_sale_orders.cancel.sources.'.$order->cancellation_source) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                            @if (in_array('cancelled_by', $visibleColumns, true))
                                <td>
                                    {{ filled($order->cancelledBy?->name)
                                        ? $order->cancelledBy->name
                                        : __('online_sale_orders.cancel.system_actor') }}
                                </td>
                            @endif
                            @if (in_array('cancellation_reason', $visibleColumns, true))
                                <td>{{ filled($order->cancellation_reason) ? \Illuminate\Support\Str::limit($order->cancellation_reason, 80) : '—' }}</td>
                            @endif
                            @if (in_array('cancelled_at', $visibleColumns, true))
                                <td>{{ $order->cancelled_at ? agricart_datetime($order->cancelled_at) : '—' }}</td>
                            @endif
                            @if (in_array('order_date', $visibleColumns, true))
                                <td>{{ $order->order_date?->format('Y-m-d') }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ agricart_datetime($order->created_at) }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $order->id }})">
                                    {{ __('online_sale_orders.actions.view_short') }}
                                </button>
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
