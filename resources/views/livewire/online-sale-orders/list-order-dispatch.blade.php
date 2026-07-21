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

    <div class="agricart-online-order__list-note" role="note">
        {{ __('order_dispatch.list.queue_note') }}
    </div>

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
                        @if (in_array('queue_document', $visibleColumns, true))
                            <th>{{ __('online_sale_orders.list.col_queue_document') }}</th>
                        @endif
                        @if (in_array('packing_progress', $visibleColumns, true))
                            <th>{{ __('order_dispatch.list.col_packing_progress') }}</th>
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
                            @if (in_array('queue_document', $visibleColumns, true))
                                <td>{{ $order->queueDocumentLabel() }}</td>
                            @endif
                            @if (in_array('packing_progress', $visibleColumns, true))
                                @php $progress = $orderProgress[$order->id] ?? ['sent' => 0, 'total' => 0, 'remaining' => 0, 'complete' => false]; @endphp
                                <td>
                                    <span @class([
                                        'agricart-online-order__receipt-badge',
                                        'is-waiting' => ! ($progress['complete'] ?? false) && ($progress['sent'] ?? 0) === 0,
                                        'is-verified' => $progress['complete'] ?? false,
                                        'is-notified' => ! ($progress['complete'] ?? false) && ($progress['sent'] ?? 0) > 0,
                                    ])>
                                        {{ __('order_dispatch.list.packing_progress', [
                                            'sent' => $progress['sent'] ?? 0,
                                            'total' => $progress['total'] ?? 0,
                                        ]) }}
                                    </span>
                                </td>
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
                    <fieldset class="agricart-order-forward__docs">
                        <legend>{{ __('online_sale_orders.forward.document_label') }}</legend>
                        <label class="agricart-order-forward__doc">
                            <input type="radio" wire:model.live="forwardDocument" value="packing" />
                            <span>{{ __('online_sale_orders.forward.document_packing') }}</span>
                        </label>
                        <label class="agricart-order-forward__doc">
                            <input type="radio" wire:model.live="forwardDocument" value="invoice" />
                            <span>{{ __('online_sale_orders.forward.document_invoice') }}</span>
                        </label>
                    </fieldset>

                    @if ($forwardStatusMessage)
                        <p class="agricart-order-forward__status" role="status">{{ $forwardStatusMessage }}</p>
                    @endif

                    @if ($forwardDocument === 'packing')
                        @if ($forwardProgress)
                            <p class="agricart-order-forward__progress">
                                {{ __('order_dispatch.messages.packing_partial', [
                                    'sent' => $forwardProgress['sent'],
                                    'total' => $forwardProgress['total'],
                                    'remaining' => $forwardProgress['remaining'],
                                ]) }}
                            </p>
                        @endif

                        <section class="agricart-order-forward__section">
                            <h3>{{ __('online_sale_orders.forward.items_heading') }}</h3>
                            <p class="agricart-order-forward__hint">{{ __('online_sale_orders.forward.items_hint') }}</p>
                            <ul class="agricart-order-forward__items">
                                @foreach ($forwardLineStates as $line)
                                    <li wire:key="forward-item-{{ $line['item_id'] }}" class="agricart-order-forward__item">
                                        @if ($line['sent'])
                                            <label class="agricart-order-forward__item-label is-sent">
                                                <input type="checkbox" disabled checked />
                                                <span>
                                                    <strong>{{ $line['label'] }}</strong>
                                                    <span>× {{ $line['quantity'] }}</span>
                                                    <em>{{ __('online_sale_orders.forward.item_sent_to', ['supplier' => $line['supplier_name'] ?: '—']) }}</em>
                                                </span>
                                            </label>
                                        @else
                                            <label class="agricart-order-forward__item-label">
                                                <input
                                                    type="checkbox"
                                                    value="{{ $line['item_id'] }}"
                                                    wire:model.live="forwardSelectedItemIds"
                                                />
                                                <span>
                                                    <strong>{{ $line['label'] }}</strong>
                                                    <span>× {{ $line['quantity'] }}</span>
                                                    <em>{{ __('online_sale_orders.forward.item_remaining') }}</em>
                                                </span>
                                            </label>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </section>

                        <section class="agricart-order-forward__section">
                            <h3>{{ __('online_sale_orders.forward.matched_suppliers_heading') }}</h3>
                            <div class="agricart-order-forward__search">
                                <label class="agricart-purchase-form__label" for="forward-supplier-search">{{ __('online_sale_orders.forward.supplier_search') }}</label>
                                <input
                                    id="forward-supplier-search"
                                    type="search"
                                    class="agricart-purchase-form__input agricart-purchase-form__input--compact"
                                    wire:model.live.debounce.300ms="forwardSupplierSearch"
                                    placeholder="{{ __('online_sale_orders.forward.supplier_search_placeholder') }}"
                                />
                            </div>
                            @if ($forwardMatchedSuppliers === [])
                                <p class="agricart-order-forward__empty">{{ __('online_sale_orders.forward.suppliers_empty') }}</p>
                            @else
                                <ul class="agricart-order-forward__list">
                                    @foreach ($forwardMatchedSuppliers as $supplier)
                                        @include('livewire.online-sale-orders.partials.forward-supplier-row', ['supplier' => $supplier])
                                    @endforeach
                                </ul>
                            @endif
                        </section>

                        @if ($forwardOtherSuppliers !== [] || filled($forwardSupplierSearch))
                            <section class="agricart-order-forward__section">
                                <h3>{{ __('online_sale_orders.forward.other_suppliers_heading') }}</h3>
                                @if ($forwardOtherSuppliers === [])
                                    <p class="agricart-order-forward__empty">{{ __('online_sale_orders.forward.supplier_search_empty') }}</p>
                                @else
                                    <ul class="agricart-order-forward__list">
                                        @foreach ($forwardOtherSuppliers as $supplier)
                                            @include('livewire.online-sale-orders.partials.forward-supplier-row', ['supplier' => $supplier])
                                        @endforeach
                                    </ul>
                                @endif
                            </section>
                        @endif
                    @else
                        <section class="agricart-order-forward__section">
                            <h3>{{ __('online_sale_orders.forward.staff_heading') }}</h3>
                            <p class="agricart-order-forward__hint">{{ __('online_sale_orders.forward.staff_hint') }}</p>
                            @if ($forwardStaff === [])
                                <p class="agricart-order-forward__empty">{{ __('online_sale_orders.forward.staff_empty') }}</p>
                            @else
                                <ul class="agricart-order-forward__list">
                                    @foreach ($forwardStaff as $staff)
                                        <li wire:key="forward-staff-{{ $staff['id'] }}" class="agricart-order-forward__row">
                                            <div class="agricart-order-forward__identity">
                                                <strong>{{ $staff['name'] }}</strong>
                                                <span>{{ $staff['phone'] ?? __('online_sale_orders.forward.no_phone') }}</span>
                                                @if ($staff['email'])
                                                    <span>{{ $staff['email'] }}</span>
                                                @endif
                                            </div>
                                            <div class="agricart-order-forward__channels">
                                                <button
                                                    type="button"
                                                    class="agricart-categories__action"
                                                    wire:click="forwardWhatsApp('staff', {{ $staff['id'] }})"
                                                    @disabled(! $staff['can_whatsapp'])
                                                    title="{{ $staff['can_whatsapp'] ? '' : __('online_sale_orders.forward.no_phone') }}"
                                                >{{ __('order_dispatch.actions.whatsapp') }}</button>
                                                <button
                                                    type="button"
                                                    class="agricart-categories__action"
                                                    wire:click="forwardEmail('staff', {{ $staff['id'] }})"
                                                    @disabled(! $staff['can_email'])
                                                    title="{{ $staff['can_email'] ? '' : __('online_sale_orders.forward.no_email') }}"
                                                >{{ __('order_dispatch.actions.email') }}</button>
                                                <button
                                                    type="button"
                                                    class="agricart-categories__action"
                                                    wire:click="forwardOpen('staff', {{ $staff['id'] }})"
                                                >{{ __('online_sale_orders.forward.open') }}</button>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </section>
                    @endif
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

        if (url) {
            window.open(url, '_blank');
        }
    });
</script>
@endscript
