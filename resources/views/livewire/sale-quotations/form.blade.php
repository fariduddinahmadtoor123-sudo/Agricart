<div
    class="agricart-purchase-form"
    x-on:sale-quotation-form-open.window="$wire.openForm($event.detail.quotationId ?? null, $event.detail.mode ?? 'create')"
    x-on:sale-quotation-form-action.window="$wire.handlePanelAction($event.detail?.action ?? '')"
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
                <span class="agricart-sale-form__top-label">{{ __('sale_quotations.fields.quotation_number') }}</span>
                <strong>{{ $quotation_number ?: __('sale_quotations.hints.auto_on_save') }}</strong>
            </span>
            <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
            <span class="agricart-sale-form__top-item">
                <span class="agricart-sale-form__top-label">{{ __('sale_quotations.fields.quotation_date') }}</span>
                <strong>{{ $this->formattedQuotationDate() }}</strong>
            </span>
            <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
            <span class="agricart-sale-form__top-item">
                <span class="agricart-sale-form__top-label">{{ __('sale_quotations.fields.prepared_by') }}</span>
                <strong>{{ $cashierName }}</strong>
            </span>
            @unless ($this->isReadonly())
                <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                <span class="agricart-sale-form__top-item agricart-sale-form__top-item--locale">
                    <label class="agricart-sale-form__top-label" for="sale-quotation-locale">{{ __('sale_quotations.fields.locale') }}</label>
                    <select id="sale-quotation-locale" class="agricart-sale-form__locale-select" wire:model.live="locale">
                        @foreach ($localeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </span>
            @else
                <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                <span class="agricart-sale-form__top-item">
                    <span class="agricart-sale-form__top-label">{{ __('sale_quotations.fields.locale') }}</span>
                    <strong>{{ __('sale_quotations.locales.' . $locale) }}</strong>
                </span>
            @endunless
        </p>

        <div class="agricart-purchase-form__banner-body">
            <div class="agricart-purchase-form__party-line agricart-purchase-form__party-line--supplier">
                @if ($selectedCustomer)
                    <p class="agricart-purchase-form__party-summary">
                        <strong class="agricart-purchase-form__party-name">{{ $selectedCustomer->displayLabel() }}</strong>
                        @if (filled($selectedCustomer->primaryPhone()))
                            <span class="agricart-purchase-form__party-sep" aria-hidden="true">·</span>
                            <span>{{ $selectedCustomer->primaryPhone() }}</span>
                        @endif
                        @if (! $this->isReadonly())
                            <button type="button" class="agricart-purchase-form__party-change" wire:click="clearCustomer">{{ __('sale_quotations.actions.clear_customer') }}</button>
                        @endif
                    </p>
                @elseif (! $this->isReadonly())
                    <div class="agricart-purchase-form__supplier-search-wrap">
                        <input
                            type="search"
                            class="agricart-purchase-form__input agricart-purchase-form__input--banner"
                            wire:model.live.debounce.250ms="customer_search"
                            placeholder="{{ __('sale_quotations.hints.customer_search') }}"
                            autocomplete="off"
                        />
                        @if ($customerResults !== [])
                            <ul class="agricart-purchase-form__results agricart-purchase-form__results--banner">
                                @foreach ($customerResults as $result)
                                    <li wire:key="sale-quotation-customer-{{ $result['id'] }}">
                                        <button type="button" class="agricart-purchase-form__result" wire:click="selectCustomer({{ $result['id'] }})">
                                            {{ $result['label'] }} · {{ $result['phone'] }}
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
        </div>
    </header>

    @unless ($this->isReadonly())
        <div class="agricart-purchase-form__search">
            <div class="agricart-purchase-form__search-row">
                <label class="agricart-purchase-form__search-label" for="sale-quotation-product-search">{{ __('sale_quotations.fields.product_search') }}</label>
                <input
                    id="sale-quotation-product-search"
                    type="search"
                    class="agricart-purchase-form__input agricart-purchase-form__search-input"
                    wire:model.live.debounce.250ms="product_search"
                    wire:keydown.enter.prevent="scanProduct"
                    placeholder="{{ __('sale_quotations.hints.product_search') }}"
                    autocomplete="off"
                />
                <button type="button" class="agricart-purchase-form__search-btn" wire:click="scanProduct">{{ __('sale_quotations.actions.add_product') }}</button>
            </div>
            @if ($productResults !== [])
                <ul class="agricart-purchase-form__results">
                    @foreach ($productResults as $result)
                        <li wire:key="sale-quotation-product-{{ $result['id'] }}">
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

    <div class="agricart-purchase-form__grid-wrap">
        <table class="agricart-purchase-form__grid">
            <thead>
                <tr>
                    <th>#</th>
                    <th></th>
                    <th>{{ __('sale_quotations.columns.product_name') }}</th>
                    <th>{{ __('sale_quotations.columns.brand') }}</th>
                    <th>{{ __('sale_quotations.columns.quantity') }}</th>
                    <th>{{ __('sale_quotations.columns.unit') }}</th>
                    <th>{{ __('sale_quotations.columns.tier') }}</th>
                    <th>{{ __('sale_quotations.columns.unit_price') }}</th>
                    <th>{{ __('sale_quotations.columns.line_total') }}</th>
                    @unless ($this->isReadonly())
                        <th></th>
                    @endunless
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $index => $row)
                    <tr wire:key="sale-quotation-row-{{ $index }}-{{ $row['product_id'] ?? $index }}">
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
                        <td>
                            <input
                                type="text"
                                class="agricart-purchase-form__cell-input"
                                wire:model.live.debounce.400ms="rows.{{ $index }}.quantity"
                                placeholder="0"
                                @disabled($this->isReadonly())
                            />
                        </td>
                        <td>{{ $row['unit_label'] ?: '—' }}</td>
                        <td>{{ $this->tierLabel((string) ($row['price_tier_key'] ?? 'retail')) }}</td>
                        <td>
                            <input
                                type="text"
                                class="agricart-purchase-form__cell-input"
                                wire:model.live.debounce.400ms="rows.{{ $index }}.unit_price"
                                wire:change="markPriceManual({{ $index }})"
                                @disabled($this->isReadonly())
                            />
                        </td>
                        <td>{{ $this->displayPrice((string) ($row['line_total'] ?? '')) }}</td>
                        @unless ($this->isReadonly())
                            <td>
                                <button type="button" class="agricart-purchase-form__row-remove" wire:click="removeRow({{ $index }})">{{ __('sale_quotations.actions.remove') }}</button>
                            </td>
                        @endunless
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="agricart-pos__grid-empty">&nbsp;</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="agricart-sale-form__status-row agricart-sale-form__status-row--three-fields">
        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="sale-quotation-discount-type">{{ __('sale_quotations.fields.discount_type') }}</label>
            <select id="sale-quotation-discount-type" class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model.live="discount_type" @disabled($this->isReadonly())>
                @foreach ($discountOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="sale-quotation-discount-value">{{ __('sale_quotations.fields.discount_value') }}</label>
            <input id="sale-quotation-discount-value" type="text" class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model.live.debounce.400ms="discount_value" @disabled($this->isReadonly()) />
        </div>

        <div class="agricart-purchase-form__field">
            <label class="agricart-purchase-form__label" for="sale-quotation-print-controls">{{ __('sale_quotations.fields.print_controls') }}</label>
            <select id="sale-quotation-print-controls" class="agricart-purchase-form__input agricart-purchase-form__input--compact" wire:model.live="print_controls_mode" @disabled($this->isReadonly())>
                @foreach ($printControlsOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($controlsPreview !== [])
        <section class="agricart-sale-form__controls-preview" aria-label="{{ __('sale_quotations.fields.print_controls') }}">
            <h3 class="agricart-sale-form__controls-preview-title">{{ __('sale_quotations.print.controls_heading') }}</h3>
            <ul class="agricart-sale-form__controls-preview-list">
                @foreach ($controlsPreview as $line)
                    <li>
                        <strong>{{ $line['product'] }}</strong>
                        <span>{{ $line['controls'] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="agricart-sale-form__notes-block">
        <label class="agricart-purchase-form__label" for="sale-quotation-notes">{{ __('sale_quotations.fields.notes') }}</label>
        <textarea
            id="sale-quotation-notes"
            class="agricart-sale-form__notes-input"
            wire:model.live.debounce.300ms="notes"
            rows="4"
            placeholder="{{ __('sale_quotations.hints.notes') }}"
            @disabled($this->isReadonly())
        ></textarea>
    </div>

    <div class="agricart-purchase-form__totals">
        <div><span>{{ __('sale_quotations.fields.subtotal') }}</span><strong>{{ $this->displayPrice($subtotal) }}</strong></div>
        @if ((float) str_replace(',', '', $discount_amount) > 0)
            <div><span>{{ __('sale_quotations.fields.discount') }}</span><strong>- {{ $this->displayPrice($discount_amount) }}</strong></div>
        @endif
        <div><span>{{ __('sale_quotations.fields.quotation_amount') }}</span><strong>{{ $this->displayPrice($quotation_amount) }}</strong></div>
    </div>
</div>
