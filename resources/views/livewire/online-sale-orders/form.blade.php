<div
    class="agricart-purchase-form"
    x-on:online-order-form-open.window="$wire.openForm($event.detail.orderId ?? null, $event.detail.mode ?? 'view')"
    x-on:online-order-form-action.window="$wire.handlePanelAction($event.detail?.action ?? '')"
>
    @if ($statusMessage)
        <div @class([
            'agricart-purchase-form__status',
            'is-success' => $statusType === 'success',
            'is-error' => $statusType === 'error',
            'is-warning' => $statusType === 'warning',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    @if ($this->isLocked())
        <div class="agricart-purchase-form__status is-warning">
            {{ __('online_sale_orders.hints.converted_locked') }}
        </div>
    @endif

    <header class="agricart-purchase-form__banner agricart-sale-form__banner">
        <p class="agricart-sale-form__top-line">
            <span class="agricart-sale-form__top-item">
                <span class="agricart-sale-form__top-label">{{ __('online_sale_orders.fields.order_number') }}</span>
                <strong>{{ $order_number ?: '—' }}</strong>
            </span>
            <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
            <span class="agricart-sale-form__top-item">
                <span class="agricart-sale-form__top-label">{{ __('online_sale_orders.fields.order_date') }}</span>
                <strong>{{ $this->formattedOrderDate() }}</strong>
            </span>
            <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
            <span class="agricart-sale-form__top-item">
                <span class="agricart-sale-form__top-label">{{ __('online_sale_orders.fields.order_status') }}</span>
                @if ($this->canConfirmPayment())
                    <select class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model.live="order_status">
                        @foreach ($orderStatusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                @else
                    <strong>{{ $order_status ? __('online_sale_orders.order_statuses.' . $order_status) : '—' }}</strong>
                @endif
            </span>
            @if (filled($linked_sale_number))
                <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                <span class="agricart-sale-form__top-item">
                    <span class="agricart-sale-form__top-label">{{ __('online_sale_orders.fields.pos_invoice') }}</span>
                    <strong><code>{{ $linked_sale_number }}</code></strong>
                </span>
            @endif
        </p>

        @if ($selectedCustomer)
            <div class="agricart-purchase-form__banner-body">
                <div class="agricart-purchase-form__party-line agricart-purchase-form__party-line--supplier">
                    <p class="agricart-purchase-form__party-summary">
                        <strong class="agricart-purchase-form__party-name">{{ $selectedCustomer->displayLabel() }}</strong>
                        @if (filled($selectedCustomer->primaryPhone()))
                            <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                            <span>{{ $selectedCustomer->primaryPhone() }}</span>
                        @endif
                    </p>
                </div>
            </div>
        @endif
    </header>

    @if (filled($ship_contact_name) || filled($ship_address) || filled($ship_city))
        <div class="agricart-online-order__ship-block">
            <h3 class="agricart-online-order__section-title">{{ __('online_sale_orders.sections.shipping') }}</h3>
            <p class="agricart-online-order__ship-line">
                @if (filled($ship_contact_name))
                    <strong>{{ $ship_contact_name }}</strong>
                @endif
                @if (filled($ship_phone))
                    <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                    <span>{{ $ship_phone }}</span>
                @endif
            </p>
            @if (filled($ship_address) || filled($ship_city))
                <p class="agricart-online-order__ship-line">{{ trim($ship_address . ($ship_city ? ', ' . $ship_city : '')) }}</p>
            @endif
        </div>
    @endif

    <div class="agricart-purchase-form__grid-wrap">
        <table class="agricart-purchase-form__grid">
            <thead>
                <tr>
                    <th>#</th>
                    <th></th>
                    <th>{{ __('online_sale_orders.columns.product_name') }}</th>
                    <th>{{ __('online_sale_orders.columns.brand') }}</th>
                    <th>{{ __('online_sale_orders.columns.quantity') }}</th>
                    <th>{{ __('online_sale_orders.columns.unit_price') }}</th>
                    <th>{{ __('online_sale_orders.columns.line_total') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $index => $row)
                    <tr wire:key="online-order-row-{{ $index }}-{{ $row['product_id'] ?? $index }}">
                        <td>{{ $index + 1 }}</td>
                        <td>
                            @if ($this->rowImageUrl($row['image_path'] ?? null))
                                <img src="{{ $this->rowImageUrl($row['image_path']) }}" alt="" class="agricart-purchase-form__thumb" />
                            @else
                                <span class="agricart-purchase-form__thumb-placeholder">—</span>
                            @endif
                        </td>
                        <td class="agricart-purchase-form__name">
                            {{ app()->getLocale() === 'ur' && filled($row['product_name_ur'] ?? null) ? $row['product_name_ur'] : $row['product_name_en'] }}
                        </td>
                        <td>{{ app()->getLocale() === 'ur' && filled($row['brand_name_ur'] ?? null) ? $row['brand_name_ur'] : ($row['brand_name_en'] ?: '—') }}</td>
                        <td>{{ $row['quantity'] ?: '—' }}</td>
                        <td>{{ $this->displayPrice((string) ($row['unit_price'] ?? '')) }}</td>
                        <td>{{ $this->displayPrice((string) ($row['line_total'] ?? '')) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="agricart-pos__grid-empty">&nbsp;</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="agricart-online-order__declared-payment">
        <h3 class="agricart-online-order__section-title">{{ __('online_sale_orders.sections.customer_payment') }}</h3>
        <p class="agricart-online-order__meta-line">
            <span class="agricart-sale-form__top-label">{{ __('online_sale_orders.fields.customer_payment_method') }}</span>
            <strong>
                @if (filled($customer_payment_method))
                    {{ __('pos.payment_methods.' . $customer_payment_method) }}
                @else
                    —
                @endif
            </strong>
        </p>
        @if (filled($customer_payment_note))
            <p class="agricart-online-order__meta-line">{{ $customer_payment_note }}</p>
        @endif
    </div>

    <h3 class="agricart-online-order__section-title">{{ __('order_dispatch.sections.fulfillment') }}</h3>
    @if ($orderId)
        <div class="agricart-online-order__print-actions">
            <button type="button" class="agricart-categories__action" wire:click="printPackingSlip">
                {{ __('online_sale_orders.actions.packing_slip') }}
            </button>
        </div>
    @endif
    <div class="agricart-sale-form__status-row agricart-sale-form__status-row--four-fields">
        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="online-order-assigned-user">{{ __('order_dispatch.fields.assigned_staff') }}</label>
            <select id="online-order-assigned-user" class="agricart-purchase-form__input agricart-purchase-form__input--compact agricart-purchase-form__input--status" wire:model.live="assigned_user_id" @disabled(! $this->canConfirmPayment())>
                <option value="">{{ __('order_dispatch.hints.all_staff') }}</option>
                @foreach ($staffOptions as $staff)
                    <option value="{{ $staff['id'] }}">{{ $staff['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="agricart-purchase-form__field agricart-purchase-form__field--wide">
            <label class="agricart-purchase-form__label" for="online-order-fulfillment-status">{{ __('order_dispatch.fields.fulfillment_status') }}</label>
            <select id="online-order-fulfillment-status" class="agricart-purchase-form__input agricart-purchase-form__input--compact agricart-purchase-form__input--status" wire:model.live="fulfillment_status" @disabled(! $this->canConfirmPayment())>
                @foreach ($fulfillmentStatusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($suggestedSuppliers !== [])
        <div class="agricart-online-order__supplier-block">
            <h3 class="agricart-online-order__section-title">{{ __('order_dispatch.sections.suppliers') }}</h3>
            <ul class="agricart-online-order__supplier-list">
                @foreach ($suggestedSuppliers as $supplier)
                    @php
                        $supplierKey = (string) $supplier['id'];
                        $fulfillment = $supplierFulfillment[$supplierKey] ?? null;
                        $isAssigned = $supplier['is_assigned'] ?? false;
                        $currentStatus = $fulfillment['status'] ?? ($supplier['status'] ?? 'pending');
                    @endphp
                    <li wire:key="online-order-supplier-{{ $supplier['id'] }}" class="agricart-online-order__supplier-item agricart-online-order__supplier-item--stacked">
                        <div class="agricart-online-order__supplier-head">
                            <label class="agricart-online-order__supplier-check">
                                <input
                                    type="checkbox"
                                    value="{{ $supplier['id'] }}"
                                    wire:model.live="selectedSupplierIds"
                                    @disabled(! $this->canConfirmPayment())
                                />
                                <span>{{ $supplier['label'] }}</span>
                            </label>
                        </div>

                        @if ($isAssigned)
                            <div class="agricart-online-order__supplier-fulfillment">
                                <div class="agricart-purchase-form__field">
                                    <label class="agricart-purchase-form__label">{{ __('order_dispatch.fields.supplier_status') }}</label>
                                    <select
                                        class="agricart-purchase-form__input agricart-purchase-form__input--compact agricart-purchase-form__input--status"
                                        wire:model.live="supplierFulfillment.{{ $supplierKey }}.status"
                                        @disabled(! $this->canConfirmPayment())
                                    >
                                        @foreach ($supplierStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @if (filled($fulfillment['acknowledged_at'] ?? null))
                                    <p class="agricart-online-order__meta-line">{{ __('order_dispatch.fields.acknowledged_at') }}: {{ $fulfillment['acknowledged_at'] }}</p>
                                @endif

                                @if (($fulfillment['status'] ?? '') === 'shipped')
                                    <div class="agricart-online-order__supplier-ship-grid">
                                        <div class="agricart-purchase-form__field">
                                            <label class="agricart-purchase-form__label">{{ __('order_dispatch.fields.carrier_name') }}</label>
                                            <input type="text" class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model.live.debounce.400ms="supplierFulfillment.{{ $supplierKey }}.carrier_name" @disabled(! $this->canConfirmPayment()) placeholder="{{ __('order_dispatch.hints.carrier_name') }}" />
                                        </div>
                                        <div class="agricart-purchase-form__field">
                                            <label class="agricart-purchase-form__label">{{ __('order_dispatch.fields.tracking_number') }}</label>
                                            <input type="text" class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model.live.debounce.400ms="supplierFulfillment.{{ $supplierKey }}.tracking_number" @disabled(! $this->canConfirmPayment()) />
                                        </div>
                                        <div class="agricart-purchase-form__field">
                                            <label class="agricart-purchase-form__label">{{ __('order_dispatch.fields.shipping_reference') }}</label>
                                            <input type="text" class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model.live.debounce.400ms="supplierFulfillment.{{ $supplierKey }}.shipping_reference" @disabled(! $this->canConfirmPayment()) placeholder="{{ __('order_dispatch.hints.shipping_reference') }}" />
                                        </div>
                                        <div class="agricart-purchase-form__field agricart-purchase-form__field--wide">
                                            <label class="agricart-purchase-form__label">{{ __('order_dispatch.fields.shipping_proof') }}</label>
                                            @if ($this->canConfirmPayment())
                                                <input type="file" accept="image/*,.pdf" class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model="supplierProofUploads.{{ $supplier['id'] }}" />
                                            @endif
                                            @if (filled($fulfillment['shipping_proof_path'] ?? null))
                                                <a href="{{ $supplier['proof_url'] ?? '#' }}" target="_blank" rel="noopener" class="agricart-online-order__proof-link">{{ __('order_dispatch.actions.view_proof') }}</a>
                                            @endif
                                        </div>
                                        @if (filled($fulfillment['shipped_at'] ?? null))
                                            <p class="agricart-online-order__meta-line">{{ __('order_dispatch.fields.shipped_at') }}: {{ $fulfillment['shipped_at'] }}</p>
                                        @endif
                                    </div>
                                @endif

                                <div class="agricart-purchase-form__field agricart-purchase-form__field--wide">
                                    <label class="agricart-purchase-form__label">{{ __('order_dispatch.fields.supplier_notes') }}</label>
                                    <textarea class="agricart-sale-form__notes-input" rows="2" wire:model.live.debounce.300ms="supplierFulfillment.{{ $supplierKey }}.supplier_response_notes" @disabled(! $this->canConfirmPayment()) placeholder="{{ __('order_dispatch.hints.supplier_notes') }}"></textarea>
                                </div>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @elseif ($this->canConfirmPayment() && $orderId)
        <p class="agricart-online-order__meta-line">{{ __('order_dispatch.hints.no_suppliers') }}</p>
    @endif

    <h3 class="agricart-online-order__section-title">{{ __('online_sale_orders.sections.admin_payment') }}</h3>
    <div class="agricart-sale-form__status-row agricart-sale-form__status-row--four-fields">
        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="online-order-payment-method">{{ __('pos.fields.payment_method') }}</label>
            <select id="online-order-payment-method" class="agricart-purchase-form__input agricart-purchase-form__input--compact agricart-purchase-form__input--status" wire:model.live="payment_method" @disabled(! $this->canConfirmPayment())>
                <option value="">{{ __('pos.hints.select_method') }}</option>
                @foreach ($paymentMethodOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="online-order-bank-account">{{ __('pos.fields.bank_account') }}</label>
            @if ($bankAccountOptions === [])
                <select id="online-order-bank-account" class="agricart-purchase-form__input agricart-purchase-form__input--compact agricart-purchase-form__input--status" disabled>
                    <option value="">{{ $payment_method === 'bank_transfer' ? __('pos.hints.no_banks') : __('pos.hints.select_bank') }}</option>
                </select>
            @else
                <select id="online-order-bank-account" class="agricart-purchase-form__input agricart-purchase-form__input--compact agricart-purchase-form__input--status" wire:model.live="bank_account_id" @disabled(! $this->canConfirmPayment() || $payment_method !== 'bank_transfer')>
                    <option value="">{{ __('pos.hints.select_bank') }}</option>
                    @foreach ($bankAccountOptions as $bank)
                        <option value="{{ $bank['id'] }}">{{ $bank['label'] }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="online-order-payment-status">{{ __('pos.fields.payment_status') }}</label>
            <select id="online-order-payment-status" class="agricart-purchase-form__input agricart-purchase-form__input--compact agricart-purchase-form__input--status" wire:model.live="payment_status" @disabled(! $this->canConfirmPayment())>
                <option value="">{{ __('pos.hints.select_status') }}</option>
                @foreach ($paymentOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="online-order-amount-paid">{{ __('pos.fields.amount_paid') }}</label>
            <input id="online-order-amount-paid" type="text" class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model.live.debounce.400ms="amount_paid" @disabled(! $this->isAmountPaidEditable()) />
        </div>
    </div>

    @if (filled($customer_notes))
        <div class="agricart-sale-form__notes-block">
            <label class="agricart-purchase-form__label">{{ __('online_sale_orders.fields.customer_notes') }}</label>
            <p class="agricart-online-order__note-readonly">{{ $customer_notes }}</p>
        </div>
    @endif

    <div class="agricart-sale-form__notes-block">
        <label class="agricart-purchase-form__label" for="online-order-admin-notes">{{ __('online_sale_orders.fields.admin_notes') }}</label>
        <textarea
            id="online-order-admin-notes"
            class="agricart-sale-form__notes-input"
            wire:model.live.debounce.300ms="admin_notes"
            rows="3"
            placeholder="{{ __('online_sale_orders.hints.admin_notes') }}"
            @disabled(! $this->canConfirmPayment())
        ></textarea>
    </div>

    <div class="agricart-purchase-form__totals">
        <div><span>{{ __('online_sale_orders.fields.subtotal') }}</span><strong>{{ $this->displayPrice($subtotal) }}</strong></div>
        <div><span>{{ __('online_sale_orders.fields.total_amount') }}</span><strong>{{ $this->displayPrice($total_amount) }}</strong></div>
        <div><span>{{ __('online_sale_orders.fields.balance_due') }}</span><strong>{{ $this->displayPrice($balance_due) }}</strong></div>
    </div>
</div>
