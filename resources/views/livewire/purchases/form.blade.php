@php use Filament\Support\Icons\Heroicon; @endphp

<div class="agricart-purchase-form">
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

    <header class="agricart-purchase-form__banner">
        <div class="agricart-purchase-form__banner-body">
            <div class="agricart-purchase-form__party-line agricart-purchase-form__party-line--supplier">
                @if ($selectedSupplier)
                    <p class="agricart-purchase-form__party-summary">
                        <strong class="agricart-purchase-form__party-name">{{ $this->supplierDisplayName($selectedSupplier) }}</strong>
                        @if (filled($this->supplierAddressLine($selectedSupplier)))
                            <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                            <span>{{ $this->supplierAddressLine($selectedSupplier) }}</span>
                        @endif
                        @if ($supplierPhone = $this->supplierPhone($selectedSupplier))
                            <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                            <span>{{ $supplierPhone }}</span>
                        @endif
                        @if ($supplier_balance_display !== '0' && $supplier_balance_display !== '0.00')
                            <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                            <span class="agricart-purchase-form__party-balance">{{ __('purchases.fields.supplier_balance') }}: <strong>{{ $supplier_balance_display }}</strong></span>
                        @endif
                        @if (! $this->isReadonly())
                            <button type="button" class="agricart-purchase-form__party-change" wire:click="clearSupplier">{{ __('purchases.actions.change_supplier') }}</button>
                        @endif
                    </p>
                @elseif (! $this->isReadonly())
                    <div class="agricart-purchase-form__supplier-search-wrap">
                        <input
                            type="search"
                            class="agricart-purchase-form__input agricart-purchase-form__input--banner"
                            wire:model.live.debounce.250ms="supplier_search"
                            placeholder="{{ __('purchases.hints.select_supplier') }}"
                            autocomplete="off"
                        />
                        @if ($supplierResults !== [])
                            <ul class="agricart-purchase-form__results agricart-purchase-form__results--banner">
                                @foreach ($supplierResults as $result)
                                    <li wire:key="purchase-supplier-{{ $result['id'] }}">
                                        <button type="button" class="agricart-purchase-form__result" wire:click="selectSupplier({{ $result['id'] }})">
                                            {{ $result['label'] }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @else
                    <p class="agricart-purchase-form__party-summary is-muted">—</p>
                @endif
            </div>

            <p class="agricart-purchase-form__party-summary agricart-purchase-form__party-summary--buyer">
                @if (filled($buyerName))
                    <strong class="agricart-purchase-form__party-name">{{ $buyerName }}</strong>
                @endif
                @if (filled($buyerAddress))
                    <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                    <span>{{ $buyerAddress }}</span>
                @endif
                @if (filled($buyerPhone))
                    <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                    <span>{{ $buyerPhone }}</span>
                @endif
            </p>
        </div>

        <div class="agricart-purchase-form__banner-meta">
            <div class="agricart-purchase-form__banner-meta-chips">
                <span class="agricart-purchase-form__chip">
                    <span class="agricart-purchase-form__chip-label">{{ __('purchases.fields.purchase_number') }}</span>
                    <strong>{{ $purchase_number ?: __('purchases.hints.auto_on_save') }}</strong>
                </span>
                <span class="agricart-purchase-form__chip">
                    <span class="agricart-purchase-form__chip-label">{{ __('purchases.fields.purchase_date') }}</span>
                    <strong>{{ $formattedPurchaseDate }}</strong>
                </span>
            </div>

            <div class="agricart-purchase-form__bill-slot">
                @if ($vendorBillPreviewUrl)
                    <div class="agricart-purchase-form__bill-upload is-filled">
                        <img src="{{ $vendorBillPreviewUrl }}" alt="" class="agricart-purchase-form__bill-preview" />
                        <div class="agricart-purchase-form__bill-overlay">
                            <a href="{{ $vendorBillPreviewUrl }}" target="_blank" class="agricart-purchase-form__bill-overlay-btn" title="{{ __('purchases.actions.view_bill') }}">
                                <x-filament::icon :icon="Heroicon::OutlinedEye" class="agricart-purchase-form__bill-overlay-icon" />
                            </a>
                            @unless ($this->isReadonly())
                                <button type="button" class="agricart-purchase-form__bill-overlay-btn is-danger" wire:click="removeVendorBill" title="{{ __('purchases.actions.remove') }}">
                                    <x-filament::icon :icon="Heroicon::OutlinedTrash" class="agricart-purchase-form__bill-overlay-icon" />
                                </button>
                            @endunless
                        </div>
                    </div>
                @elseif (! $this->isReadonly())
                    <label class="agricart-purchase-form__bill-upload" title="{{ __('purchases.actions.bill_upload') }}">
                        <x-filament::icon :icon="Heroicon::OutlinedDocumentText" class="agricart-purchase-form__bill-icon" />
                        <span class="agricart-purchase-form__bill-label">{{ __('purchases.actions.bill_upload') }}</span>
                        <input type="file" accept="image/*" wire:model="vendorBillUpload" class="agricart-category-form__file-input" />
                    </label>
                    <div wire:loading wire:target="vendorBillUpload" class="agricart-purchase-form__bill-loading">{{ __('purchases.actions.bill_uploading') }}</div>
                @endif
            </div>
        </div>
    </header>

    @unless ($this->isReadonly())
        <div class="agricart-purchase-form__search">
            <div class="agricart-purchase-form__search-row">
                <label class="agricart-purchase-form__search-label" for="purchase-product-search">{{ __('purchases.fields.product_search') }}</label>
                <input
                    id="purchase-product-search"
                    type="search"
                    class="agricart-purchase-form__input agricart-purchase-form__search-input"
                    wire:model.live.debounce.250ms="product_search"
                    wire:keydown.enter.prevent="scanProduct"
                    placeholder="{{ __('purchases.hints.product_search') }}"
                    autocomplete="off"
                />
                <button type="button" class="agricart-purchase-form__search-btn" wire:click="scanProduct">{{ __('purchases.actions.add_product') }}</button>
            </div>
            @if ($productResults !== [])
                <ul class="agricart-purchase-form__results">
                    @foreach ($productResults as $result)
                        <li wire:key="purchase-product-{{ $result['id'] }}">
                            <button type="button" class="agricart-purchase-form__result" wire:click="addProduct({{ $result['id'] }})">
                                @if ($result['image_url'])
                                    <img src="{{ $result['image_url'] }}" alt="" class="agricart-purchase-form__result-thumb" />
                                @endif
                                <span>{{ $result['label'] }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endunless

    @if ($rows !== [])
        <div class="agricart-purchase-form__grid-wrap">
            <table class="agricart-purchase-form__grid">
                <thead>
                    <tr>
                        <th>{{ __('purchases.columns.sr') }}</th>
                        <th>{{ __('purchases.columns.thumb') }}</th>
                        <th>{{ __('purchases.columns.product_name') }}</th>
                        <th>{{ __('purchases.columns.required_qty') }}</th>
                        <th>{{ __('purchases.columns.purchase_qty') }}</th>
                        @if ($delivery_status === \App\Models\Purchase::DELIVERY_PARTIAL)
                            <th>{{ __('purchases.columns.received_qty') }}</th>
                        @endif
                        <th>{{ __('purchases.columns.previous_price') }}</th>
                        <th>{{ __('purchases.columns.current_rate') }}</th>
                        <th>{{ __('purchases.columns.landing_cost') }}</th>
                        <th>{{ __('purchases.columns.cost_price') }}</th>
                        <th>{{ __('purchases.columns.sale_price') }}</th>
                        <th>{{ __('purchases.columns.line_total') }}</th>
                        @unless ($this->isReadonly())
                            <th></th>
                        @endunless
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $index => $row)
                        <tr wire:key="purchase-row-{{ $index }}-{{ $row['product_id'] ?? $index }}">
                            <td>{{ $index + 1 }}</td>
                            <td>
                                @if ($this->rowImageUrl($row['image_path'] ?? null))
                                    <img src="{{ $this->rowImageUrl($row['image_path']) }}" alt="" class="agricart-purchase-form__thumb" />
                                @else
                                    <span class="agricart-purchase-form__thumb-placeholder">—</span>
                                @endif
                            </td>
                            <td class="agricart-purchase-form__name">{{ $this->rowDisplayName($row) }}</td>
                            @foreach (
                                $delivery_status === \App\Models\Purchase::DELIVERY_PARTIAL
                                    ? ['required_qty', 'purchase_qty', 'received_qty', 'previous_price', 'current_rate', 'landing_cost', 'cost_price', 'sale_price', 'line_total']
                                    : ['required_qty', 'purchase_qty', 'previous_price', 'current_rate', 'landing_cost', 'cost_price', 'sale_price', 'line_total']
                                as $field
                            )
                                <td>
                                    <input
                                        type="text"
                                        class="agricart-purchase-form__cell-input"
                                        wire:model.live.debounce.400ms="rows.{{ $index }}.{{ $field }}"
                                        @disabled($this->isReadonly() || in_array($field, ['cost_price', 'line_total'], true))
                                    />
                                </td>
                            @endforeach
                            @unless ($this->isReadonly())
                                <td>
                                    <button type="button" class="agricart-purchase-form__remove" wire:click="removeRow({{ $index }})">{{ __('purchases.actions.remove') }}</button>
                                </td>
                            @endunless
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="agricart-purchase-form__status-grid">
        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="delivery-status">{{ __('purchases.fields.delivery_status') }}</label>
            <select id="delivery-status" class="agricart-purchase-form__input agricart-purchase-form__input--compact agricart-purchase-form__input--status" wire:model.live="delivery_status" @disabled($this->isReadonly())>
                <option value="">{{ __('purchases.hints.select_status') }}</option>
                @foreach ($deliveryOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="payment-status">{{ __('purchases.fields.payment_status') }}</label>
            <select id="payment-status" class="agricart-purchase-form__input agricart-purchase-form__input--compact agricart-purchase-form__input--status" wire:model.live="payment_status" @disabled($this->isReadonly())>
                <option value="">{{ __('purchases.hints.select_status') }}</option>
                @foreach ($paymentOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="amount-paid">{{ __('purchases.fields.amount_paid') }}</label>
            <input id="amount-paid" type="text" class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model.live.debounce.400ms="amount_paid" @disabled($this->isReadonly()) />
        </div>

        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="dispute-status">{{ __('purchases.fields.dispute_status') }}</label>
            <select id="dispute-status" class="agricart-purchase-form__input agricart-purchase-form__input--compact agricart-purchase-form__input--status" wire:model.live="dispute_status" @disabled($this->isReadonly())>
                <option value="">{{ __('purchases.hints.select_status') }}</option>
                @foreach ($disputeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="purchase-locale">{{ __('purchases.fields.locale') }}</label>
            <select id="purchase-locale" class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model.live="locale" @disabled($this->isReadonly())>
                @foreach ($localeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="purchase-purchaser">{{ __('purchases.fields.purchaser') }}</label>
            <select id="purchase-purchaser" class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model.live="purchaser_id" @disabled($this->isReadonly())>
                <option value="">{{ __('purchases.hints.select_purchaser') }}</option>
                @foreach ($purchaserOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label">{{ __('purchases.fields.created_by') }}</label>
            <div class="agricart-purchase-form__readonly agricart-purchase-form__readonly--compact">{{ $creatorName }}</div>
        </div>

        <div class="agricart-purchase-form__field agricart-purchase-form__field--notes">
            <label class="agricart-purchase-form__label" for="purchase-notes">{{ __('purchases.fields.notes') }}</label>
            <textarea
                id="purchase-notes"
                class="agricart-purchase-form__notes-input"
                wire:model.live.debounce.300ms="notes"
                rows="1"
                placeholder="{{ __('purchases.hints.notes_dispute') }}"
                x-data="{
                    resize() {
                        $el.style.height = 'auto';
                        $el.style.height = Math.max($el.scrollHeight, 30) + 'px';
                    },
                }"
                x-init="resize()"
                x-on:input="resize()"
                @disabled($this->isReadonly())
            ></textarea>
        </div>
    </div>

    <div class="agricart-purchase-form__totals">
        <div><span>{{ __('purchases.fields.subtotal') }}</span><strong>{{ $subtotal }}</strong></div>
        <div><span>{{ __('purchases.fields.total_amount') }}</span><strong>{{ $total_amount }}</strong></div>
        <div><span>{{ __('purchases.fields.balance_due') }}</span><strong>{{ $balance_due }}</strong></div>
    </div>
</div>
