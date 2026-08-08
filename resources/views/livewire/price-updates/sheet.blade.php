<div
    class="agricart-purchase-form agricart-price-update-form"
    x-data
    x-init="
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('price-update-form-busy', {
                detail: { saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:price-update-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:price-update-form-open.window="$wire.openForm($event.detail.productId ?? null)"
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

    <p class="agricart-form-panel__hint">{{ __('price_updates.intro') }}</p>

    @if ($canEdit || $canView)
        <div class="agricart-price-update-form__print-bar">
            <label class="agricart-price-update-form__print-field">
                <span class="agricart-price-update-form__print-label">{{ __('price_updates.print.name_language') }}</span>
                <select wire:model.live="printLocale" class="agricart-purchase-form__input agricart-price-update-form__print-select">
                    <option value="en">{{ __('price_updates.locales.en') }}</option>
                    <option value="ur">{{ __('price_updates.locales.ur') }}</option>
                    <option value="both">{{ __('price_updates.locales.both') }}</option>
                </select>
            </label>
            <p class="agricart-price-update-form__print-hint">{{ __('price_updates.print.toolbar_hint') }}</p>
        </div>
    @endif

    @if ($canEdit)
        <div class="agricart-purchase-form__search agricart-price-update-form__load-bar">
            <div class="agricart-price-update-form__load-field">
                <label class="agricart-purchase-form__search-label" for="agricart-price-update-product">{{ __('price_updates.fields.add_product') }}</label>
                <div class="agricart-price-update-form__load-input-row">
                    <input
                        id="agricart-price-update-product"
                        type="search"
                        class="agricart-purchase-form__input agricart-purchase-form__search-input"
                        wire:model.live.debounce.200ms="productQuery"
                        wire:keydown.enter.prevent="scanOrAddProduct"
                        placeholder="{{ __('price_updates.fields.add_product_placeholder') }}"
                        autocomplete="off"
                    />
                    <button type="button" class="agricart-purchase-form__search-btn" wire:click="scanOrAddProduct">
                        {{ __('price_updates.actions.add_product') }}
                    </button>
                </div>
                @if ($productResults !== [])
                    <ul class="agricart-purchase-form__results">
                        @foreach ($productResults as $result)
                            <li wire:key="price-update-result-{{ $result['id'] }}">
                                <button type="button" class="agricart-purchase-form__result" wire:click="addProduct({{ $result['id'] }})">
                                    @if (! empty($result['image_url']))
                                        <img src="{{ $result['image_url'] }}" alt="" class="agricart-purchase-form__result-thumb" />
                                    @endif
                                    <span>{{ $result['label'] }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="agricart-price-update-form__load-field">
                <label class="agricart-purchase-form__search-label" for="agricart-price-update-category">{{ __('price_updates.fields.load_category') }}</label>
                <input
                    id="agricart-price-update-category"
                    type="search"
                    class="agricart-purchase-form__input agricart-purchase-form__search-input"
                    wire:model.live.debounce.200ms="categoryQuery"
                    placeholder="{{ __('price_updates.fields.load_category_placeholder') }}"
                    autocomplete="off"
                />
                @if ($categoryResults !== [])
                    <ul class="agricart-purchase-form__results">
                        @foreach ($categoryResults as $result)
                            <li wire:key="price-update-category-{{ $result['id'] }}">
                                <button type="button" class="agricart-purchase-form__result" wire:click="addProductsFromCategory({{ $result['id'] }})">
                                    <span>{{ $result['label'] }}</span>
                                    <span class="agricart-price-update-form__category-hint">{{ __('price_updates.actions.load_category') }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="agricart-price-update-form__load-field">
                <label class="agricart-purchase-form__search-label" for="agricart-price-update-brand">{{ __('price_updates.fields.load_brand') }}</label>
                <input
                    id="agricart-price-update-brand"
                    type="search"
                    class="agricart-purchase-form__input agricart-purchase-form__search-input"
                    wire:model.live.debounce.200ms="brandQuery"
                    placeholder="{{ __('price_updates.fields.load_brand_placeholder') }}"
                    autocomplete="off"
                />
                @if ($brandResults !== [])
                    <ul class="agricart-purchase-form__results">
                        @foreach ($brandResults as $result)
                            <li wire:key="price-update-brand-{{ $result['id'] }}">
                                <button type="button" class="agricart-purchase-form__result" wire:click="addProductsFromBrand({{ $result['id'] }})">
                                    <span>{{ $result['label'] }}</span>
                                    <span class="agricart-price-update-form__category-hint">{{ __('price_updates.actions.load_brand') }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif

    @if ($rows === [])
        <div class="agricart-purchase-form__empty">
            <p>{{ __('price_updates.messages.empty_sheet') }}</p>
        </div>
    @else
        <div class="agricart-purchase-form__grid-wrap">
            <table class="agricart-purchase-form__grid agricart-price-update-form__grid">
                <thead>
                    <tr>
                        <th>{{ __('price_updates.headers.sr') }}</th>
                        <th>{{ __('price_updates.headers.thumb') }}</th>
                        <th class="agricart-purchase-form__name">{{ __('price_updates.headers.name') }}</th>
                        <th>{{ __('price_updates.headers.buy') }}</th>
                        @foreach ($tierColumns as $tier)
                            <th title="{{ $tier['label'] }}{{ filled($tier['markup']) ? ' +'.$tier['markup'].'%' : '' }}">
                                {{ $tier['label'] }}
                                @if (filled($tier['markup']))
                                    <span class="agricart-price-update-form__pct">+{{ $tier['markup'] }}%</span>
                                @endif
                            </th>
                            <th title="{{ $tier['qty_label'] }}">{{ $tier['qty_label'] }}</th>
                        @endforeach
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $index => $row)
                        <tr wire:key="{{ $row['row_id'] }}">
                            <td>{{ $index + 1 }}</td>
                            <td>
                                @if (! empty($row['thumb_url']))
                                    <img src="{{ $row['thumb_url'] }}" alt="" class="agricart-purchase-form__thumb" />
                                @else
                                    <span class="agricart-purchase-form__thumb-placeholder">—</span>
                                @endif
                            </td>
                            <td class="agricart-purchase-form__name">
                                <strong>{{ $row['name'] }}</strong>
                                @if (filled($row['name_ur'] ?? null))
                                    <div class="agricart-price-updates__name-ur" dir="rtl">{{ $row['name_ur'] }}</div>
                                @endif
                                @if (filled($row['code'] ?? null))
                                    <div class="agricart-price-updates__meta"><code>{{ $row['code'] }}</code></div>
                                @endif
                            </td>
                            <td>
                                <input
                                    type="text"
                                    inputmode="decimal"
                                    class="agricart-purchase-form__cell-input agricart-price-update-form__cell--price"
                                    wire:model.live.debounce.400ms="rows.{{ $index }}.last_buy_price"
                                    @disabled(! $canEdit)
                                    title="{{ __('price_updates.hints.buy_fills_empty') }}"
                                />
                            </td>
                            @foreach ($tierColumns as $tier)
                                @php
                                    $priceField = $tier['price_field'];
                                    $isChannel = str_starts_with($priceField, 'channel:');
                                    $channelKey = $isChannel ? substr($priceField, 8) : null;
                                @endphp
                                <td>
                                    @if ($isChannel)
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            class="agricart-purchase-form__cell-input agricart-price-update-form__cell--price"
                                            wire:model.live="rows.{{ $index }}.channel_prices.{{ $channelKey }}"
                                            @disabled(! $canEdit)
                                            title="{{ __('price_updates.hints.price_editable') }}"
                                        />
                                    @else
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            class="agricart-purchase-form__cell-input agricart-price-update-form__cell--price"
                                            wire:model.live="rows.{{ $index }}.{{ $priceField }}"
                                            @disabled(! $canEdit)
                                            title="{{ __('price_updates.hints.price_editable') }}"
                                        />
                                    @endif
                                </td>
                                <td>
                                    <input
                                        type="text"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        class="agricart-purchase-form__cell-input agricart-price-update-form__cell--qty"
                                        wire:model.live="rows.{{ $index }}.price_tier_quantities.{{ $priceField }}"
                                        @disabled(! $canEdit)
                                        title="{{ __('price_updates.hints.quantity_editable') }}"
                                    />
                                </td>
                            @endforeach
                            <td class="agricart-price-update-form__row-actions">
                                @if ($canEdit)
                                    <button
                                        type="button"
                                        class="agricart-purchase-form__remove agricart-price-update-form__markups"
                                        wire:click="applyMarkups({{ $index }})"
                                        title="{{ __('price_updates.hints.apply_markups') }}"
                                    >
                                        {{ __('price_updates.actions.apply_markups') }}
                                    </button>
                                    <button type="button" class="agricart-purchase-form__remove" wire:click="removeRow('{{ $row['row_id'] }}')">
                                        {{ __('price_updates.actions.remove') }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@script
<script>
    Livewire.on('price-update-open-print', (payload) => {
        const url = payload?.url ?? payload?.[0]?.url;
        if (url) window.open(url, '_blank');
    });
</script>
@endscript
