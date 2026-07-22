<div class="agricart-categories agricart-shipped-orders">
    <div class="agricart-online-order__list-note" role="note">
        {{ __('shipped_orders.list.note') }}
    </div>

    @if ($orders->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ $hasListConstraints ? __('shipped_orders.list.no_results') : __('shipped_orders.list.empty') }}</p>
            @unless ($hasListConstraints)
                <p class="agricart-categories__empty-hint">{{ __('shipped_orders.list.empty_hint') }}</p>
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
                        @if (in_array('fulfillment_status', $visibleColumns, true))
                            <th>{{ __('order_dispatch.list.col_fulfillment') }}</th>
                        @endif
                        @if (in_array('shipping', $visibleColumns, true))
                            <th>{{ __('pending_shipments.list.col_shipping') }}</th>
                        @endif
                        @if (in_array('shipped_at', $visibleColumns, true))
                            <th>{{ __('shipped_orders.list.col_shipped') }}</th>
                        @endif
                        <th>{{ __('online_sale_orders.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        @php
                            $parcels = $order->customerShippingParcels();
                            $shippedAt = $order->supplierAssignments
                                ->pluck('shipped_at')
                                ->merge($order->staffAssignments->pluck('shipped_at'))
                                ->filter()
                                ->sortDesc()
                                ->first();
                        @endphp
                        <tr wire:key="shipped-order-{{ $order->id }}">
                            @if (in_array('order_number', $visibleColumns, true))
                                <td><code>{{ $order->order_number }}</code></td>
                            @endif
                            @if (in_array('customer', $visibleColumns, true))
                                <td>{{ $order->customer?->displayLabel() ?? '—' }}</td>
                            @endif
                            @if (in_array('total_amount', $visibleColumns, true))
                                <td>{{ number_format((float) $order->total_amount, 2) }}</td>
                            @endif
                            @if (in_array('fulfillment_status', $visibleColumns, true))
                                <td>{{ __('order_dispatch.fulfillment_statuses.' . ($order->fulfillment_status ?: 'awaiting_assignment')) }}</td>
                            @endif
                            @if (in_array('shipping', $visibleColumns, true))
                                <td>
                                    @forelse ($parcels as $parcel)
                                        <div>
                                            {{ $parcel['carrier'] ?: '—' }}
                                            @if ($parcel['tracking']) / {{ $parcel['tracking'] }} @endif
                                            @if ($parcel['bilty']) / {{ __('pending_shipments.list.bilty', ['number' => $parcel['bilty']]) }} @endif
                                        </div>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                            @endif
                            @if (in_array('shipped_at', $visibleColumns, true))
                                <td>{{ $shippedAt?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—' }}</td>
                            @endif
                            <td>
                                <button type="button" class="agricart-categories__link" wire:click="openOrder({{ $order->id }})">
                                    {{ $canEdit ? __('logistics_ship.actions.ship') : __('logistics_ship.actions.view') }}
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
