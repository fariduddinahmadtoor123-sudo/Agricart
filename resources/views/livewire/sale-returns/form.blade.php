<div
    class="agricart-purchase-form"
    x-on:sale-return-form-open.window="$wire.openForm($event.detail.returnId ?? null, $event.detail.mode ?? 'create')"
    x-on:sale-return-form-action.window="$wire.handlePanelAction($event.detail?.action ?? '')"
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

    <header class="agricart-purchase-form__banner agricart-sale-form__banner">
        <p class="agricart-sale-form__top-line">
            <span class="agricart-sale-form__top-item">
                <span class="agricart-sale-form__top-label">{{ __('sale_returns.fields.return_number') }}</span>
                <strong>{{ $return_number ?: __('sale_returns.hints.auto_on_save') }}</strong>
            </span>
            <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
            <span class="agricart-sale-form__top-item">
                <span class="agricart-sale-form__top-label">{{ __('sale_returns.fields.return_date') }}</span>
                @unless ($this->isReadonly())
                    <input type="date" class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model.live="return_date" />
                @else
                    <strong>{{ $this->formattedReturnDate() }}</strong>
                @endunless
            </span>
            <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
            <span class="agricart-sale-form__top-item">
                <span class="agricart-sale-form__top-label">{{ __('sale_returns.fields.processed_by') }}</span>
                <strong>{{ $processedByName ?: '—' }}</strong>
            </span>
        </p>

        @if ($selectedCustomer)
            <div class="agricart-purchase-form__banner-body">
                <div class="agricart-purchase-form__party-line agricart-purchase-form__party-line--supplier">
                    <p class="agricart-purchase-form__party-summary">
                        <span class="agricart-sale-form__top-label">{{ __('sale_returns.fields.customer') }}</span>
                        <strong class="agricart-purchase-form__party-name">{{ $selectedCustomer->displayLabel() }}</strong>
                        @if (filled($selectedCustomer->primaryPhone()))
                            <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                            <span>{{ $selectedCustomer->primaryPhone() }}</span>
                        @endif
                        @if (! $this->isReadonly() && $sale_id)
                            <button type="button" class="agricart-purchase-form__party-change" wire:click="clearLoadedInvoice">{{ __('sale_returns.actions.clear_invoice') }}</button>
                        @endif
                    </p>
                </div>
            </div>
        @endif
    </header>

    @unless ($this->isReadonly())
        <div class="agricart-purchase-form__search">
            <div class="agricart-purchase-form__search-row">
                <label class="agricart-purchase-form__search-label" for="sale-return-invoice">{{ __('sale_returns.fields.sale_number') }}</label>
                <input
                    id="sale-return-invoice"
                    type="text"
                    class="agricart-purchase-form__input agricart-purchase-form__search-input"
                    wire:model="sale_number"
                    placeholder="{{ __('sale_returns.hints.sale_number') }}"
                    autocomplete="off"
                />
                <button
                    type="button"
                    class="agricart-purchase-form__search-btn"
                    wire:click="loadInvoice"
                >
                    {{ __('sale_returns.actions.load_invoice') }}
                </button>
            </div>
        </div>
    @endunless

    <div class="agricart-purchase-form__grid-wrap">
        <table class="agricart-purchase-form__grid">
            <thead>
                <tr>
                    <th>#</th>
                    <th></th>
                    <th>{{ __('sale_returns.columns.product_name') }}</th>
                    <th>{{ __('sale_returns.columns.brand') }}</th>
                    <th>{{ __('sale_returns.columns.sold_quantity') }}</th>
                    <th>{{ __('sale_returns.columns.unit_price') }}</th>
                    <th>{{ __('sale_returns.columns.return_quantity') }}</th>
                    <th>{{ __('sale_returns.columns.return_amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $index => $row)
                    <tr wire:key="sale-return-row-{{ $index }}-{{ $row['product_id'] ?? $index }}">
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
                        <td>{{ $row['sold_quantity'] ?: '—' }}</td>
                        <td>{{ $this->displayPrice((string) ($row['unit_price'] ?? '')) }}</td>
                        <td>
                            <input
                                type="text"
                                class="agricart-purchase-form__cell-input"
                                wire:model.live.debounce.400ms="rows.{{ $index }}.return_quantity"
                                placeholder="0"
                                @disabled($this->isReadonly())
                            />
                        </td>
                        <td>{{ $this->displayPrice((string) ($row['return_amount'] ?? '')) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="agricart-pos__grid-empty">&nbsp;</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="agricart-sale-form__status-row agricart-sale-form__status-row--four-fields">
        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="sale-return-payment-method">{{ __('pos.fields.payment_method') }}</label>
            <select id="sale-return-payment-method" class="agricart-purchase-form__input agricart-purchase-form__input--compact agricart-purchase-form__input--status" wire:model.live="payment_method" @disabled($this->isReadonly())>
                <option value="">{{ __('pos.hints.select_method') }}</option>
                @foreach ($paymentMethodOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="sale-return-bank-account">{{ __('pos.fields.bank_account') }}</label>
            @if ($bankAccountOptions === [])
                <select
                    id="sale-return-bank-account"
                    class="agricart-purchase-form__input agricart-purchase-form__input--compact agricart-purchase-form__input--status"
                    disabled
                >
                    <option value="">{{ $payment_method === 'bank_transfer' ? __('pos.hints.no_banks') : __('pos.hints.select_bank') }}</option>
                </select>
            @else
                <select
                    id="sale-return-bank-account"
                    class="agricart-purchase-form__input agricart-purchase-form__input--compact agricart-purchase-form__input--status"
                    wire:model.live="bank_account_id"
                    @disabled($this->isReadonly() || $payment_method !== 'bank_transfer')
                >
                    <option value="">{{ __('pos.hints.select_bank') }}</option>
                    @foreach ($bankAccountOptions as $bank)
                        <option value="{{ $bank['id'] }}">{{ $bank['label'] }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="sale-return-payment-status">{{ __('pos.fields.payment_status') }}</label>
            <select id="sale-return-payment-status" class="agricart-purchase-form__input agricart-purchase-form__input--compact agricart-purchase-form__input--status" wire:model.live="payment_status" @disabled($this->isReadonly())>
                <option value="">{{ __('pos.hints.select_status') }}</option>
                @foreach ($paymentOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="sale-return-amount-paid">{{ __('pos.fields.amount_paid') }}</label>
            <input id="sale-return-amount-paid" type="text" class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model.live.debounce.400ms="amount_paid" @disabled(! $this->isAmountPaidEditable()) />
        </div>
    </div>

    <div class="agricart-sale-form__notes-block">
        <label class="agricart-purchase-form__label" for="sale-return-notes">{{ __('sale_returns.fields.notes') }}</label>
        <textarea
            id="sale-return-notes"
            class="agricart-sale-form__notes-input"
            wire:model.live.debounce.300ms="notes"
            rows="4"
            placeholder="{{ __('sale_returns.hints.notes') }}"
            @disabled($this->isReadonly())
        ></textarea>
    </div>

    <div class="agricart-purchase-form__totals">
        <div><span>{{ __('sale_returns.fields.refund_subtotal') }}</span><strong>{{ $this->displayPrice($refund_subtotal) }}</strong></div>
        <div><span>{{ __('sale_returns.fields.refund_total') }}</span><strong>{{ $this->displayPrice($refund_total) }}</strong></div>
        <div><span>{{ __('sale_returns.fields.balance_due') }}</span><strong>{{ $this->displayPrice($balance_due) }}</strong></div>
    </div>
</div>
