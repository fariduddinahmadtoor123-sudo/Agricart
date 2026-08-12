<div class="agricart-categories agricart-purchases">
    @if ($hasListConstraints)
        <div class="agricart-list-filters">
            <button type="button" class="agricart-list-filters__clear" wire:click="clearFilters">
                {{ __('online_sale_orders.list.clear_filters') }}
            </button>
        </div>
    @endif

    @if (session('online_order_status'))
        <div class="agricart-online-order__list-note" role="status">
            {{ session('online_order_status') }}
        </div>
    @endif

    <div class="agricart-online-order__list-note" role="note">
        {{ __('online_sale_orders.list.invoices_note') }}
    </div>

    @if ($orders->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ $hasListConstraints ? __('online_sale_orders.list.no_results') : __('online_sale_orders.list.invoices_empty') }}</p>
            @unless ($hasListConstraints)
                <p class="agricart-categories__empty-hint">{{ __('online_sale_orders.list.invoices_empty_hint') }}</p>
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
                        @if (in_array('sale_number', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_sale_number') }}</th>
                        @endif
                        @if (in_array('customer', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_customer') }}</th>
                        @endif
                        @if (in_array('total_amount', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_total') }}</th>
                        @endif
                        @if (in_array('payment_status', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_payment_status') }}</th>
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
                        <tr wire:key="online-order-{{ $order->id }}" class="agricart-online-order__row--queue-pending">
                            @if (in_array('order_number', $visibleColumns, true))
                                <td><code>{{ $order->order_number }}</code></td>
                            @endif
                            @if (in_array('sale_number', $visibleColumns, true))
                                <td>
                                    @if ($order->sale?->sale_number)
                                        <code>{{ $order->sale->sale_number }}</code>
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                            @if (in_array('customer', $visibleColumns, true))
                                <td>{{ $order->customer?->displayLabel() ?? '—' }}</td>
                            @endif
                            @if (in_array('total_amount', $visibleColumns, true))
                                <td>{{ number_format((float) $order->total_amount, 2) }}</td>
                            @endif
                            @if (in_array('payment_status', $visibleColumns, true))
                                <td>{{ __('pos.payment_statuses.' . ($order->payment_status ?: 'unpaid')) }}</td>
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
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action is-primary" wire:click="sendInvoiceToQueue({{ $order->id }})">
                                        {{ __('online_sale_orders.actions.send_invoice_to_queue') }}
                                    </button>
                                    <button type="button" class="agricart-categories__action is-primary" wire:click="sendPackingListToQueue({{ $order->id }})">
                                        {{ __('online_sale_orders.actions.send_packing_list_to_queue') }}
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
