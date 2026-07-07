<div class="agricart-pos agricart-purchase-form">
    @if ($statusMessage)
        <div @class([
            'agricart-purchase-form__status',
            'is-success' => $statusType === 'success',
            'is-error' => $statusType === 'danger',
            'is-warning' => $statusType === 'warning',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <section class="agricart-pos__block">
        <input
            type="search"
            class="agricart-pos__search-input"
            wire:model.live.debounce.250ms="customer_search"
            placeholder="{{ __('pos.hints.customer_search') }}"
            autocomplete="off"
            @if ($selectedCustomer) disabled @endif
        />
        @if (! $selectedCustomer && $customerResults !== [])
            <ul class="agricart-pos__results">
                @foreach ($customerResults as $result)
                    <li wire:key="pos-customer-{{ $result['id'] }}">
                        <button type="button" class="agricart-pos__result-btn" wire:click="selectCustomer({{ $result['id'] }})">
                            {{ $result['label'] }} · {{ $result['phone'] }}
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
        @if ($selectedCustomer)
            <p class="agricart-pos__selected-line">
                <span>{{ $selectedCustomer->displayLabel() }}</span>
                @if (filled($selectedCustomer->primaryPhone()))
                    <span class="agricart-pos__selected-meta">{{ $selectedCustomer->primaryPhone() }}</span>
                @endif
                <button type="button" class="agricart-pos__link-btn" wire:click="clearCustomer">{{ __('pos.actions.clear_customer') }}</button>
            </p>
        @endif
    </section>

    <section class="agricart-pos__block">
        <input
            id="pos-product-search"
            type="search"
            class="agricart-pos__search-input"
            wire:model.live.debounce.250ms="product_search"
            wire:keydown.enter.prevent="scanProduct"
            placeholder="{{ __('pos.hints.product_search') }}"
            autocomplete="off"
        />
        @if ($productResults !== [])
            <ul class="agricart-pos__results">
                @foreach ($productResults as $result)
                    <li wire:key="pos-product-{{ $result['id'] }}">
                        <button type="button" class="agricart-pos__result-btn agricart-pos__result-btn--product" wire:click="addProduct({{ $result['id'] }})">
                            @if ($result['image_url'])
                                <img src="{{ $result['image_url'] }}" alt="" class="agricart-purchase-form__result-thumb" />
                            @endif
                            <span>{{ $result['label'] }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <div class="agricart-purchase-form__grid-wrap agricart-pos__grid-wrap">
        <table class="agricart-purchase-form__grid">
            <thead>
                <tr>
                    <th>#</th>
                    <th></th>
                    <th>{{ __('pos.fields.product_name') }}</th>
                    <th>{{ __('pos.fields.brand') }}</th>
                    <th>{{ __('pos.fields.quantity') }}</th>
                    <th>{{ __('pos.fields.unit') }}</th>
                    <th>{{ __('pos.fields.tier') }}</th>
                    <th>{{ __('pos.fields.unit_price') }}</th>
                    <th>{{ __('pos.fields.line_total') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $index => $row)
                    <tr wire:key="pos-row-{{ $index }}-{{ $row['product_id'] ?? $index }}">
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
                            <input type="text" class="agricart-purchase-form__cell-input" wire:model.live.debounce.400ms="rows.{{ $index }}.quantity" placeholder="0" />
                        </td>
                        <td>{{ $row['unit_label'] ?: '—' }}</td>
                        <td>{{ $this->tierLabel((string) ($row['price_tier_key'] ?? 'retail')) }}</td>
                        <td>
                            <input
                                type="text"
                                class="agricart-purchase-form__cell-input"
                                wire:model.blur="rows.{{ $index }}.unit_price"
                                wire:change="markPriceManual({{ $index }})"
                            />
                        </td>
                        <td>{{ $this->displayPrice((string) ($row['line_total'] ?? '')) }}</td>
                        <td>
                            <button type="button" class="agricart-purchase-form__row-remove" wire:click="removeRow({{ $index }})">{{ __('pos.actions.remove') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="agricart-pos__grid-empty">{{ __('pos.hints.empty_grid') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <footer class="agricart-pos__footer">
        <div class="agricart-pos__footer-grid">
            <div class="agricart-pos__footer-col">
                <label class="agricart-form-panel__label">{{ __('pos.fields.price_display') }}</label>
                <select wire:model.live="price_display" class="fi-select-input">
                    @foreach (__('pos.price_displays') as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <label class="agricart-form-panel__label">{{ __('pos.fields.discount_type') }}</label>
                <select wire:model.live="discount_type" class="fi-select-input">
                    @foreach (__('pos.discount_types') as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                @if ($discount_type !== '')
                    <label class="agricart-form-panel__label">{{ __('pos.fields.discount_value') }}</label>
                    <input type="text" wire:model.live.debounce.300ms="discount_value" class="fi-input" />
                @endif

                <label class="agricart-form-panel__label">{{ __('pos.fields.payment_method') }}</label>
                <select wire:model="payment_method" class="fi-select-input">
                    @foreach (__('pos.payment_methods') as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <label class="agricart-form-panel__label">{{ __('pos.fields.amount_paid') }}</label>
                <input type="text" wire:model="amount_paid" class="fi-input" />

                <label class="agricart-form-panel__label flex items-center gap-2">
                    <input type="checkbox" wire:model="print_controls" />
                    <span>{{ __('pos.fields.print_controls') }}</span>
                </label>
            </div>

            <div class="agricart-pos__totals">
                <div><span>{{ __('pos.fields.subtotal') }}</span><strong>{{ $this->displayPrice((string) $subtotal) }}</strong></div>
                @if ($discountAmount > 0)
                    <div><span>{{ __('pos.fields.discount') }}</span><strong>- {{ $this->displayPrice((string) $discountAmount) }}</strong></div>
                @endif
                <div class="agricart-pos__grand-total"><span>{{ __('pos.fields.total') }}</span><strong>{{ $this->displayPrice((string) $total) }}</strong></div>
                <button type="button" class="agricart-pos__complete-btn" wire:click="completeSale" wire:loading.attr="disabled" @disabled($saving || ! $hasBillableRows)>
                    {{ __('pos.actions.complete_sale') }}
                </button>
            </div>
        </div>
    </footer>
</div>
