@php use Filament\Support\Icons\Heroicon; @endphp

<div class="agricart-purchase-form agricart-quotation-form">
    @if ($statusMessage)
        <div @class([
            'agricart-purchase-form__status',
            'is-success' => $statusType === 'success',
            'is-error' => $statusType === 'error',
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
                        @if (! $this->isReadonly())
                            <button type="button" class="agricart-purchase-form__party-change" wire:click="clearSupplier">{{ __('purchase_quotations.actions.change_supplier') }}</button>
                        @endif
                    </p>
                @elseif (! $this->isReadonly())
                    <div class="agricart-purchase-form__supplier-search-wrap">
                        <input
                            type="search"
                            class="agricart-purchase-form__input agricart-purchase-form__input--banner"
                            wire:model.live.debounce.250ms="supplier_search"
                            placeholder="{{ __('purchase_quotations.hints.select_supplier') }}"
                            autocomplete="off"
                        />
                        @if ($supplierResults !== [])
                            <ul class="agricart-purchase-form__results agricart-purchase-form__results--banner">
                                @foreach ($supplierResults as $result)
                                    <li wire:key="quotation-supplier-{{ $result['id'] }}">
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
                    <span class="agricart-purchase-form__chip-label">{{ __('purchase_quotations.fields.quotation_number') }}</span>
                    <strong>{{ $quotation_number ?: __('purchase_quotations.hints.auto_on_save') }}</strong>
                </span>
                <span class="agricart-purchase-form__chip">
                    <span class="agricart-purchase-form__chip-label">{{ __('purchase_quotations.fields.quotation_date') }}</span>
                    <strong>{{ $formattedQuotationDate }}</strong>
                </span>
                <span class="agricart-purchase-form__chip agricart-quotation-form__locale-chip">
                    <span class="agricart-purchase-form__chip-label">{{ __('purchase_quotations.fields.locale') }}</span>
                    @if ($this->isReadonly())
                        <strong>{{ $localeOptions[$locale] ?? $locale }}</strong>
                    @else
                        <select class="agricart-quotation-form__locale-select" wire:model.live="locale">
                            @foreach ($localeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif
                </span>
            </div>
        </div>
    </header>

    @unless ($this->isReadonly())
        <div class="agricart-purchase-form__search-row agricart-purchase-form__search-row--products">
            <label class="agricart-purchase-form__search-label" for="quotation-product-search">{{ __('purchase_quotations.fields.product_search') }}</label>
            <input
                id="quotation-product-search"
                type="search"
                class="agricart-purchase-form__input agricart-purchase-form__search-input"
                wire:model.live.debounce.250ms="product_search"
                wire:keydown.enter.prevent="scanProduct"
                placeholder="{{ __('purchase_quotations.hints.product_search') }}"
                autocomplete="off"
                @disabled($supplier_id === null)
            />
            <button type="button" class="agricart-purchase-form__search-btn" wire:click="scanProduct" @disabled($supplier_id === null)>
                {{ __('purchase_quotations.actions.add_product') }}
            </button>
        </div>

        @if ($productResults !== [])
            <ul class="agricart-purchase-form__results">
                @foreach ($productResults as $result)
                    <li wire:key="quotation-product-{{ $result['id'] }}">
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
    @endunless

    @if ($rows === [])
        <div class="agricart-purchase-form__empty">
            <p>{{ __('purchase_quotations.form.empty') }}</p>
        </div>
    @else
        <div class="agricart-purchase-form__table-wrap">
            <table class="agricart-purchase-form__table">
                <thead>
                    <tr>
                        <th>{{ __('purchase_quotations.columns.sr') }}</th>
                        <th>{{ __('purchase_quotations.columns.thumb') }}</th>
                        <th>{{ __('purchase_quotations.columns.product_name') }}</th>
                        <th>{{ __('purchase_quotations.columns.required_qty') }}</th>
                        <th>{{ __('purchase_quotations.columns.previous_price') }}</th>
                        @unless ($this->isReadonly())
                            <th></th>
                        @endunless
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $index => $row)
                        <tr wire:key="quotation-row-{{ $index }}-{{ $row['product_id'] ?? $index }}">
                            <td>{{ $index + 1 }}</td>
                            <td>
                                @if ($thumb = $this->rowImageUrl($row['image_path'] ?? null))
                                    <img src="{{ $thumb }}" alt="" class="agricart-purchase-form__thumb" />
                                @endif
                            </td>
                            <td class="agricart-purchase-form__name">{{ $this->rowDisplayName($row) }}</td>
                            <td>
                                @if ($this->isReadonly())
                                    {{ $row['required_qty'] ?? '' }}
                                @else
                                    <input type="text" class="agricart-purchase-form__cell-input" wire:model.live.debounce.400ms="rows.{{ $index }}.required_qty" />
                                @endif
                            </td>
                            <td>
                                @if ($this->isReadonly())
                                    {{ $row['previous_price'] ?? '' }}
                                @else
                                    <input type="text" class="agricart-purchase-form__cell-input" wire:model.live.debounce.400ms="rows.{{ $index }}.previous_price" />
                                @endif
                            </td>
                            @unless ($this->isReadonly())
                                <td>
                                    <button type="button" class="agricart-purchase-form__row-remove" wire:click="removeRow({{ $index }})">
                                        {{ __('purchase_quotations.actions.remove') }}
                                    </button>
                                </td>
                            @endunless
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="agricart-quotation-form__notes-row">
        <label class="agricart-purchase-form__label" for="quotation-notes">{{ __('purchase_quotations.fields.notes') }}</label>
        <textarea
            id="quotation-notes"
            class="agricart-purchase-form__notes-input"
            wire:model.live.debounce.300ms="notes"
            rows="1"
            placeholder="{{ __('purchase_quotations.hints.notes') }}"
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
