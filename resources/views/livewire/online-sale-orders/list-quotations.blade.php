<div class="agricart-categories agricart-purchases">
    <div class="agricart-online-order__list-note" role="note">
        {{ __('online_sale_orders.list.quotations_note') }}
    </div>

    @if ($activeFilters !== [])
        <div class="agricart-list-filters">
            @foreach ($activeFilters as $filter)
                <button
                    type="button"
                    class="agricart-list-filters__chip"
                    wire:click="removeFilter('{{ $filter['field'] }}')"
                    wire:key="online-quotation-filter-{{ $filter['field'] }}"
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
            <p>{{ $hasListConstraints ? __('online_sale_orders.list.no_results') : __('online_sale_orders.list.quotations_empty') }}</p>
            @unless ($hasListConstraints)
                <p class="agricart-categories__empty-hint">{{ __('online_sale_orders.list.quotations_empty_hint') }}</p>
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
                        @if (in_array('receipt_status', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_receipt_status') }}</th>
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
                        @php
                            $proofStatus = (string) ($order->customer_payment_proof_status ?? 'none');
                            $isNotified = $proofStatus === 'submitted';
                            $isVerified = $proofStatus === 'verified';
                            $isRejected = $proofStatus === 'rejected';
                        @endphp
                        <tr
                            wire:key="online-quotation-{{ $order->id }}"
                            @class([
                                'agricart-online-order__quote-row',
                                'is-payment-notified' => $isNotified,
                                'is-payment-verified' => $isVerified,
                                'is-payment-rejected' => $isRejected,
                            ])
                        >
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
                            @if (in_array('receipt_status', $visibleColumns, true))
                                <td>
                                    <span @class([
                                        'agricart-online-order__receipt-badge',
                                        'is-notified' => $isNotified,
                                        'is-verified' => $isVerified,
                                        'is-rejected' => $isRejected,
                                        'is-waiting' => ! $isNotified && ! $isVerified && ! $isRejected,
                                    ])>
                                        @if ($isNotified)
                                            {{ __('online_sale_orders.list.receipt_notified') }}
                                        @elseif ($isVerified)
                                            {{ __('online_sale_orders.list.receipt_verified') }}
                                        @elseif ($isRejected)
                                            {{ __('online_sale_orders.list.receipt_rejected') }}
                                        @else
                                            {{ __('online_sale_orders.list.receipt_waiting') }}
                                        @endif
                                    </span>
                                </td>
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
