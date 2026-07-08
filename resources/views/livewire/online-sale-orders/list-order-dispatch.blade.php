<div class="agricart-categories agricart-order-dispatch">
    @if ($activeFilters !== [])
        <div class="agricart-list-filters">
            @foreach ($activeFilters as $filter)
                <button
                    type="button"
                    class="agricart-list-filters__chip"
                    wire:click="removeFilter('{{ $filter['field'] }}')"
                    wire:key="dispatch-filter-{{ $filter['field'] }}"
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
            <p>{{ $hasListConstraints ? __('order_dispatch.list.no_results') : __('order_dispatch.list.empty') }}</p>
            @unless ($hasListConstraints)
                <p class="agricart-categories__empty-hint">{{ __('order_dispatch.list.empty_hint') }}</p>
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
                        @if (in_array('assigned_user', $visibleColumns, true))
                            <th>{{ __('order_dispatch.list.col_staff') }}</th>
                        @endif
                        @if (in_array('suppliers', $visibleColumns, true))
                            <th>{{ __('order_dispatch.list.col_suppliers') }}</th>
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
                        <tr wire:key="dispatch-order-{{ $order->id }}">
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
                            @if (in_array('assigned_user', $visibleColumns, true))
                                <td>{{ $order->assignedUser?->name ?? '—' }}</td>
                            @endif
                            @if (in_array('suppliers', $visibleColumns, true))
                                <td>
                                    @if ($order->supplierAssignments->isEmpty())
                                        —
                                    @else
                                        {{ $order->supplierAssignments->map(function ($a) {
                                            $name = $a->supplier?->displayBusinessName() ?? '—';
                                            $statusKey = $a->status === 'sent' ? 'acknowledged' : $a->status;
                                            $status = __('order_dispatch.supplier_statuses.' . $statusKey);

                                            return $name . ' (' . $status . ')';
                                        })->join(', ') }}
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
                                <button type="button" class="agricart-categories__action" wire:click="openOrder({{ $order->id }})">
                                    {{ __('order_dispatch.actions.open') }}
                                </button>
                                <button type="button" class="agricart-categories__action" wire:click="printPackingSlip({{ $order->id }})">
                                    {{ __('order_dispatch.actions.packing_slip') }}
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
