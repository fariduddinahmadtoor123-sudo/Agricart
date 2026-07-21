@php
    $headerCustomerName = $selectedCustomer?->displayLabel() ?: ($ship_contact_name ?: null);
    $headerCustomerPhone = filled($ship_phone) ? $ship_phone : ($selectedCustomer?->primaryPhone() ?: null);
    $isQuotation = ! $this->isInvoice();
    $headerAddressParts = collect([
        $headerCustomerName,
        $headerCustomerPhone,
        filled($ship_address) ? $ship_address : null,
        filled($ship_city) ? $ship_city : null,
        (! $isQuotation && filled($delivery_method_name)) ? $delivery_method_name : null,
        (! $isQuotation && filled($shipping_charge_mode_label)) ? $shipping_charge_mode_label : null,
    ])->filter()->values();
    $hasReceipt = filled($customer_bank_receipt_url);
    $receiptNotified = in_array($customer_payment_proof_status, ['submitted', 'verified'], true);
    $hasCourierInfo = $full_advance_required
        || (filled($cod_min_advance_percent) && (float) $cod_min_advance_percent > 0)
        || filled($delivery_customer_notice)
        || filled($delivery_method_name)
        || filled($shipping_charge_mode_label);
@endphp
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

    @if ($this->isLocked() && $isQuotation)
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
                <strong>{{ $order_status ? __('online_sale_orders.order_statuses.' . $order_status) : '—' }}</strong>
            </span>
            @if (filled($linked_sale_number))
                <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                <span class="agricart-sale-form__top-item">
                    <span class="agricart-sale-form__top-label">{{ __('online_sale_orders.fields.pos_invoice') }}</span>
                    <strong><code>{{ $linked_sale_number }}</code></strong>
                </span>
            @endif
        </p>

        <div class="agricart-purchase-form__banner-body">
            <div class="agricart-purchase-form__party-line agricart-purchase-form__party-line--supplier">
                <p class="agricart-purchase-form__party-summary agricart-online-order__address-inline">
                    @forelse ($headerAddressParts as $partIndex => $part)
                        @if ($partIndex === 0)
                            <strong class="agricart-purchase-form__party-name">{{ $part }}</strong>
                        @else
                            <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                            <span>{{ $part }}</span>
                        @endif
                    @empty
                        <strong class="agricart-purchase-form__party-name is-muted">—</strong>
                    @endforelse
                </p>
            </div>
        </div>
    </header>

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

    @if ($isQuotation)
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
                <input
                    id="online-order-amount-paid"
                    type="text"
                    class="agricart-purchase-form__input agricart-purchase-form__input--compact"
                    wire:model.live.debounce.400ms="amount_paid"
                    @readonly(! $this->isAmountPaidEditable())
                    @disabled(! $this->canConfirmPayment() && ! $this->isAmountPaidEditable())
                />
            </div>
        </div>
    @else
        <div class="agricart-online-order__invoice-meta">
            <p class="agricart-online-order__meta-line">
                <strong>{{ __('pos.fields.payment_status') }}:</strong>
                {{ $payment_status ? __('pos.payment_statuses.' . $payment_status) : '—' }}
                @if (filled($amount_paid))
                    <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                    {{ __('pos.fields.amount_paid') }}: {{ $this->displayPrice($amount_paid) }}
                @endif
            </p>
            @if (filled($converted_by_name) || filled($converted_at))
                <p class="agricart-online-order__meta-line">
                    <strong>{{ __('online_sale_orders.fields.converted_by') }}:</strong>
                    {{ $converted_by_name ?: '—' }}
                    @if (filled($converted_at))
                        <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                        {{ $converted_at }}
                    @endif
                </p>
            @endif
            <p class="agricart-online-order__meta-line">
                <strong>{{ __('online_sale_orders.fields.queue_status') }}:</strong>
                {{ filled($queued_at_display)
                    ? __('online_sale_orders.queue_statuses.queued')
                    : __('online_sale_orders.queue_statuses.pending') }}
                @if (filled($queued_at_display))
                    <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                    {{ $queued_at_display }}
                    <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                    {{ filled($queue_document)
                        ? __('online_sale_orders.queue_documents.' . $queue_document)
                        : __('online_sale_orders.queue_documents.invoice') }}
                @endif
            </p>
        </div>
    @endif

    @if (filled($customer_notes))
        <div class="agricart-sale-form__notes-block">
            <label class="agricart-purchase-form__label">{{ __('online_sale_orders.fields.customer_notes') }}</label>
            <p class="agricart-online-order__note-readonly">{{ $customer_notes }}</p>
        </div>
    @endif

    @if ($isQuotation)
        <div class="agricart-sale-form__notes-block">
            <label class="agricart-purchase-form__label" for="online-order-admin-notes">{{ __('online_sale_orders.fields.admin_notes') }}</label>
            <textarea
                id="online-order-admin-notes"
                class="agricart-sale-form__notes-input"
                wire:model.live.debounce.300ms="admin_notes"
                rows="2"
                placeholder="{{ __('online_sale_orders.hints.admin_notes') }}"
                @disabled(! $this->canConfirmPayment() && ! $this->canManageFulfillment())
            ></textarea>
        </div>
    @elseif (filled($admin_notes))
        <div class="agricart-sale-form__notes-block">
            <label class="agricart-purchase-form__label">{{ __('online_sale_orders.fields.admin_notes') }}</label>
            <p class="agricart-online-order__note-readonly">{{ $admin_notes }}</p>
        </div>
    @endif

    <div class="agricart-purchase-form__totals">
        <div><span>{{ __('online_sale_orders.fields.subtotal') }}</span><strong>{{ $this->displayPrice($subtotal) }}</strong></div>
        <div><span>{{ __('online_sale_orders.fields.total_amount') }}</span><strong>{{ $this->displayPrice($total_amount) }}</strong></div>
        <div><span>{{ __('online_sale_orders.fields.balance_due') }}</span><strong>{{ $this->displayPrice($balance_due) }}</strong></div>
    </div>

    @if ($isQuotation)
        <div class="agricart-online-order__top-stack">
            <div class="agricart-online-order__declared-payment">
                <h3 class="agricart-online-order__section-title">{{ __('online_sale_orders.sections.customer_payment') }}</h3>

                @if ($hasReceipt && $receiptNotified)
                    <p class="agricart-online-order__receipt-status is-ready">{{ __('online_sale_orders.payment_proof_statuses.' . $customer_payment_proof_status) }}</p>
                    <div class="agricart-online-order__customer-receipt">
                        @if ($this->customerBankReceiptIsPreviewable())
                            <a href="{{ $customer_bank_receipt_url }}" target="_blank" rel="noopener" class="agricart-online-order__receipt-preview-link">
                                <img src="{{ $customer_bank_receipt_url }}" alt="" class="agricart-online-order__receipt-preview" />
                            </a>
                        @endif
                        <a href="{{ $customer_bank_receipt_url }}" target="_blank" rel="noopener" class="agricart-online-order__proof-link">
                            {{ __('online_sale_orders.actions.view_customer_receipt') }}
                        </a>
                    </div>
                    @if (filled($customer_payment_reference))
                        <p class="agricart-online-order__meta-line">
                            <strong><code>{{ $customer_payment_reference }}</code></strong>
                        </p>
                    @endif
                @else
                    <p class="agricart-online-order__receipt-status is-pending">{{ __('online_sale_orders.hints.pending_payment') }}</p>
                @endif

                @if (filled($customer_payment_note))
                    <p class="agricart-online-order__meta-line">{{ $customer_payment_note }}</p>
                @endif
                @if (filled($payment_proof_rejection_note))
                    <p class="agricart-online-order__meta-line agricart-online-order__meta-line--reject">{{ $payment_proof_rejection_note }}</p>
                @endif

                @if ($this->canConfirmPayment() && $customer_payment_proof_status === 'submitted')
                    <div class="agricart-online-order__proof-actions">
                        <button type="button" class="agricart-categories__action is-primary" wire:click="verifyCustomerPaymentProof" wire:loading.attr="disabled">
                            {{ __('online_sale_orders.actions.verify_payment') }}
                        </button>
                        <div class="agricart-online-order__reject-row">
                            <input type="text" class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model="payment_proof_reject_input" placeholder="{{ __('online_sale_orders.hints.reject_payment_note') }}" />
                            <button type="button" class="agricart-categories__action is-danger" wire:click="rejectCustomerPaymentProof" wire:loading.attr="disabled">
                                {{ __('online_sale_orders.actions.reject_payment') }}
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            @if ($hasCourierInfo)
                <div class="agricart-online-order__ship-hints">
                    @if (filled($delivery_method_name) || filled($shipping_charge_mode_label))
                        <p class="agricart-online-order__ship-line">
                            <strong>{{ __('online_sale_orders.fields.delivery_method') }}:</strong>
                            {{ collect([$delivery_method_name, $shipping_charge_mode_label])->filter()->implode(' · ') }}
                        </p>
                    @endif
                    @if ($full_advance_required || (filled($cod_min_advance_percent) && (float) $cod_min_advance_percent > 0))
                        <p class="agricart-online-order__ship-line">
                            @if ($full_advance_required)
                                {{ __('agricart.storefront.checkout.full_advance_hint') }}
                            @else
                                {{ __('agricart.storefront.checkout.cod_advance_hint', ['percent' => $cod_min_advance_percent]) }}
                            @endif
                        </p>
                    @endif
                    @if (filled($delivery_customer_notice))
                        <p class="agricart-online-order__ship-line">{{ $delivery_customer_notice }}</p>
                    @endif
                </div>
            @endif
        </div>
    @elseif ($full_advance_required || (filled($cod_min_advance_percent) && (float) $cod_min_advance_percent > 0) || filled($delivery_customer_notice))
        <div class="agricart-online-order__ship-hints">
            @if ($full_advance_required || (filled($cod_min_advance_percent) && (float) $cod_min_advance_percent > 0))
                <p class="agricart-online-order__ship-line">
                    @if ($full_advance_required)
                        {{ __('agricart.storefront.checkout.full_advance_hint') }}
                    @else
                        {{ __('agricart.storefront.checkout.cod_advance_hint', ['percent' => $cod_min_advance_percent]) }}
                    @endif
                </p>
            @endif
            @if (filled($delivery_customer_notice))
                <p class="agricart-online-order__ship-line">{{ $delivery_customer_notice }}</p>
            @endif
        </div>
    @endif

    @if ($this->showFulfillmentSection())
        <div class="agricart-online-order__below-invoice">
            <h3 class="agricart-online-order__section-title">{{ __('order_dispatch.sections.fulfillment') }}</h3>
            <div class="agricart-sale-form__status-row agricart-sale-form__status-row--four-fields">
                <div class="agricart-purchase-form__field">
                    <label class="agricart-purchase-form__label" for="online-order-assigned-user">{{ __('order_dispatch.fields.assigned_staff') }}</label>
                    <select id="online-order-assigned-user" class="agricart-purchase-form__input agricart-purchase-form__input--compact agricart-purchase-form__input--status" wire:model.live="assigned_user_id" @disabled(! $this->canManageFulfillment())>
                        <option value="">{{ __('order_dispatch.hints.all_staff') }}</option>
                        @foreach ($staffOptions as $staff)
                            <option value="{{ $staff['id'] }}">{{ $staff['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="agricart-purchase-form__field agricart-purchase-form__field--wide">
                    <label class="agricart-purchase-form__label" for="online-order-fulfillment-status">{{ __('order_dispatch.fields.fulfillment_status') }}</label>
                    <select id="online-order-fulfillment-status" class="agricart-purchase-form__input agricart-purchase-form__input--compact agricart-purchase-form__input--status" wire:model.live="fulfillment_status" @disabled(! $this->canManageFulfillment())>
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
                            @endphp
                            <li wire:key="online-order-supplier-{{ $supplier['id'] }}" class="agricart-online-order__supplier-item agricart-online-order__supplier-item--stacked">
                                <div class="agricart-online-order__supplier-head">
                                    <label class="agricart-online-order__supplier-check">
                                        <input
                                            type="checkbox"
                                            value="{{ $supplier['id'] }}"
                                            wire:model.live="selectedSupplierIds"
                                            @disabled(! $this->canManageFulfillment())
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
                                                @disabled(! $this->canManageFulfillment())
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
                                                    <input type="text" class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model.live.debounce.400ms="supplierFulfillment.{{ $supplierKey }}.carrier_name" @disabled(! $this->canManageFulfillment()) placeholder="{{ __('order_dispatch.hints.carrier_name') }}" />
                                                </div>
                                                <div class="agricart-purchase-form__field">
                                                    <label class="agricart-purchase-form__label">{{ __('order_dispatch.fields.tracking_number') }}</label>
                                                    <input type="text" class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model.live.debounce.400ms="supplierFulfillment.{{ $supplierKey }}.tracking_number" @disabled(! $this->canManageFulfillment()) />
                                                </div>
                                                <div class="agricart-purchase-form__field">
                                                    <label class="agricart-purchase-form__label">{{ __('order_dispatch.fields.shipping_reference') }}</label>
                                                    <input type="text" class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model.live.debounce.400ms="supplierFulfillment.{{ $supplierKey }}.shipping_reference" @disabled(! $this->canManageFulfillment()) placeholder="{{ __('order_dispatch.hints.shipping_reference') }}" />
                                                </div>
                                                <div class="agricart-purchase-form__field agricart-purchase-form__field--wide">
                                                    <label class="agricart-purchase-form__label">{{ __('order_dispatch.fields.shipping_proof') }}</label>
                                                    @if ($this->canManageFulfillment())
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
                                            <textarea class="agricart-sale-form__notes-input" rows="2" wire:model.live.debounce.300ms="supplierFulfillment.{{ $supplierKey }}.supplier_response_notes" @disabled(! $this->canManageFulfillment()) placeholder="{{ __('order_dispatch.hints.supplier_notes') }}"></textarea>
                                        </div>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @elseif ($this->canManageFulfillment() && $orderId)
                <p class="agricart-online-order__meta-line">{{ __('order_dispatch.hints.no_suppliers') }}</p>
            @endif
        </div>
    @endif
</div>
