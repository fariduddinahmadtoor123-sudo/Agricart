<div class="agricart-categories agricart-pending-shipments">
    @if ($activeFilters !== [])
        <div class="agricart-list-filters">
            @foreach ($activeFilters as $filter)
                <button
                    type="button"
                    class="agricart-list-filters__chip"
                    wire:click="removeFilter('{{ $filter['field'] }}')"
                    wire:key="pending-ship-filter-{{ $filter['field'] }}"
                >
                    <span>{{ $filter['label'] }}</span>
                    <span aria-hidden="true">×</span>
                </button>
            @endforeach

            @if ($hasListConstraints)
                <button type="button" class="agricart-list-filters__clear" wire:click="clearFilters">
                    {{ __('pending_shipments.list.clear_filters') }}
                </button>
            @endif
        </div>
    @endif

    <div class="agricart-online-order__list-note" role="note">
        {{ __('pending_shipments.list.note') }}
    </div>

    @if ($orders->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ $hasListConstraints ? __('pending_shipments.list.no_results') : __('pending_shipments.list.empty') }}</p>
            @unless ($hasListConstraints)
                <p class="agricart-categories__empty-hint">{{ __('pending_shipments.list.empty_hint') }}</p>
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
                        @if (in_array('queue_document', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_queue_document') }}</th>
                        @endif
                        @if (in_array('packing_progress', $visibleColumns, true))
                            <th>{{ __('order_dispatch.list.col_packing_progress') }}</th>
                        @endif
                        @if (in_array('fulfillment_status', $visibleColumns, true))
                            <th>{{ __('order_dispatch.list.col_fulfillment') }}</th>
                        @endif
                        @if (in_array('shipping', $visibleColumns, true))
                            <th>{{ __('pending_shipments.list.col_shipping') }}</th>
                        @endif
                        @if (in_array('queued_at', $visibleColumns, true))
                            <th>{{ __('pending_shipments.list.col_queued') }}</th>
                        @endif
                        <th>{{ __('online_sale_orders.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr wire:key="pending-ship-{{ $order->id }}">
                            @if (in_array('order_number', $visibleColumns, true))
                                <td><code>{{ $order->order_number }}</code></td>
                            @endif
                            @if (in_array('customer', $visibleColumns, true))
                                <td>{{ $order->customer?->displayLabel() ?? '—' }}</td>
                            @endif
                            @if (in_array('total_amount', $visibleColumns, true))
                                <td>{{ number_format((float) $order->total_amount, 2) }}</td>
                            @endif
                            @if (in_array('queue_document', $visibleColumns, true))
                                <td>{{ $order->queueDocumentLabel() }}</td>
                            @endif
                            @if (in_array('packing_progress', $visibleColumns, true))
                                @php $progress = $orderProgress[$order->id] ?? ['sent' => 0, 'total' => 0, 'complete' => false]; @endphp
                                <td>
                                    {{ __('order_dispatch.list.packing_progress', [
                                        'sent' => $progress['sent'] ?? 0,
                                        'total' => $progress['total'] ?? 0,
                                    ]) }}
                                </td>
                            @endif
                            @if (in_array('fulfillment_status', $visibleColumns, true))
                                <td>{{ __('order_dispatch.fulfillment_statuses.' . ($order->fulfillment_status ?: 'awaiting_assignment')) }}</td>
                            @endif
                            @if (in_array('shipping', $visibleColumns, true))
                                <td>
                                    @php
                                        $parcels = $order->customerShippingParcels();
                                    @endphp
                                    @if ($parcels === [])
                                        —
                                    @else
                                        @foreach ($parcels as $parcel)
                                            <div>
                                                @if (filled($parcel['carrier']))
                                                    <span>{{ $parcel['carrier'] }}</span>
                                                @endif
                                                @if (filled($parcel['tracking']))
                                                    <code>{{ $parcel['tracking'] }}</code>
                                                @endif
                                                @if (filled($parcel['bilty']))
                                                    <span>{{ __('pending_shipments.list.bilty', ['number' => $parcel['bilty']]) }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </td>
                            @endif
                            @if (in_array('queued_at', $visibleColumns, true))
                                <td>{{ $order->queued_at?->format('Y-m-d H:i') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openOrder({{ $order->id }})">
                                    {{ __('pending_shipments.actions.open') }}
                                </button>
                                <button type="button" class="agricart-categories__action" wire:click="printOrder({{ $order->id }})">
                                    {{ __('pending_shipments.actions.print_invoice') }}
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
