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
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('order_number', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_number') }}</th>
                        @endif
                        @if (in_array('invoice_number', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_invoice_number') }}</th>
                        @endif
                        @if (in_array('customer', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_customer') }}</th>
                        @endif
                        @if (in_array('total_amount', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_total') }}</th>
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
                            @if (in_array('invoice_number', $visibleColumns, true))
                                <td>
                                    @if (filled($order->sale?->sale_number))
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
                            @if (in_array('order_date', $visibleColumns, true))
                                <td>{{ $order->order_date?->format('Y-m-d') }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openOrder({{ $order->id }})">
                                    {{ __('online_sale_orders.actions.view_short') }}
                                </button>
                                <button type="button" class="agricart-categories__action" wire:click="openForward({{ $order->id }})">
                                    {{ __('online_sale_orders.actions.forward_short') }}
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

    @if ($forwardOrder)
        <div
            class="agricart-order-forward__overlay"
            wire:click.self="closeForward"
            x-data
            @keydown.escape.window="$wire.closeForward()"
        >
            <section
                class="agricart-order-forward__panel"
                role="dialog"
                aria-modal="true"
                aria-labelledby="order-dispatch-forward-title"
            >
                <header class="agricart-order-forward__head">
                    <div>
                        <span class="agricart-order-forward__eyebrow">{{ __('online_sale_orders.forward.eyebrow') }}</span>
                        <h2 id="order-dispatch-forward-title">{{ $forwardOrder->order_number }}</h2>
                    </div>
                    <button
                        type="button"
                        class="agricart-order-forward__close"
                        wire:click="closeForward"
                        aria-label="{{ __('online_sale_orders.forward.close') }}"
                    >×</button>
                </header>

                <div class="agricart-order-forward__body">
                    @if ($forwardStatusMessage)
                        <p class="agricart-order-forward__status" role="status">{{ $forwardStatusMessage }}</p>
                    @endif

                    <section class="agricart-order-forward__section">
                        <h3>{{ __('online_sale_orders.forward.staff_heading') }}</h3>
                        <p class="agricart-order-forward__hint">{{ __('online_sale_orders.forward.staff_hint') }}</p>

                        <div class="agricart-order-forward__search">
                            <label class="agricart-purchase-form__label" for="forward-staff-search">{{ __('online_sale_orders.forward.staff_search') }}</label>
                            <input
                                id="forward-staff-search"
                                type="search"
                                class="agricart-purchase-form__input agricart-purchase-form__input--compact"
                                wire:model.live.debounce.250ms="forwardStaffSearch"
                                placeholder="{{ __('online_sale_orders.forward.staff_search_placeholder') }}"
                            />
                        </div>

                        @if ($forwardStaff === [])
                            <p class="agricart-order-forward__empty">
                                {{ filled($forwardStaffSearch)
                                    ? __('online_sale_orders.forward.staff_search_empty')
                                    : __('online_sale_orders.forward.staff_empty') }}
                            </p>
                        @else
                            <ul class="agricart-order-forward__list">
                                @foreach ($forwardStaff as $staff)
                                    <li wire:key="forward-staff-{{ $staff['id'] }}" class="agricart-order-forward__row">
                                        <div class="agricart-order-forward__identity">
                                            <strong>{{ $staff['name'] }}</strong>
                                            @if ($staff['role'])
                                                <span>{{ $staff['role'] }}</span>
                                            @endif
                                            @if ($staff['email'])
                                                <span>{{ $staff['email'] }}</span>
                                            @endif
                                            @if ($staff['is_self'])
                                                <em>{{ __('online_sale_orders.forward.staff_you') }}</em>
                                            @elseif ($staff['status'] === 'inactive')
                                                <em>{{ __('online_sale_orders.forward.staff_inactive') }}</em>
                                            @endif
                                        </div>
                                        <div class="agricart-order-forward__channels">
                                            @if ($staff['can_forward'])
                                                <button
                                                    type="button"
                                                    class="agricart-categories__action agricart-categories__action--primary"
                                                    wire:click="forwardToInbox({{ $staff['id'] }})"
                                                >{{ __('online_sale_orders.forward.send_inbox') }}</button>
                                            @else
                                                <button
                                                    type="button"
                                                    class="agricart-categories__action"
                                                    disabled
                                                    title="{{ __('online_sale_orders.forward.staff_inactive') }}"
                                                >{{ __('online_sale_orders.forward.send_inactive') }}</button>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </div>
            </section>
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

    Livewire.on('online-sale-orders-open-external', (payload) => {
        const url = payload?.url ?? payload?.[0]?.url;

        if (! url) {
            return;
        }

        const opened = window.open(url, '_blank', 'noopener,noreferrer');

        if (! opened) {
            window.location.href = url;
        }
    });
</script>
@endscript
