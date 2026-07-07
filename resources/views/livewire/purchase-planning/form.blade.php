<div class="agricart-planning-form">
    @if ($statusMessage)
        <div @class([
            'agricart-planning-form__status',
            'is-success' => $statusType === 'success',
            'is-error' => $statusType === 'error',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <div class="agricart-planning-form__meta">
        <div class="agricart-planning-form__field">
            <label class="agricart-planning-form__label">{{ __('purchase_planning.fields.planning_number') }}</label>
            <input
                type="text"
                class="agricart-planning-form__input"
                value="{{ $planning_number ?: __('purchase_planning.hints.auto_on_save') }}"
                readonly
                disabled
            />
        </div>

        <div class="agricart-planning-form__field">
            <label class="agricart-planning-form__label" for="planning-paper-size">{{ __('purchase_planning.fields.paper_size') }}</label>
            <select
                id="planning-paper-size"
                class="agricart-planning-form__input"
                wire:model.live="paper_size"
                @disabled($this->isReadonly())
            >
                @foreach ($paperSizeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="agricart-planning-form__field">
            <label class="agricart-planning-form__label" for="planning-locale">{{ __('purchase_planning.fields.locale') }}</label>
            <select
                id="planning-locale"
                class="agricart-planning-form__input"
                wire:model.live="locale"
                @disabled($this->isReadonly())
            >
                @foreach ($localeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="agricart-planning-form__field agricart-planning-form__field--wide">
            <label class="agricart-planning-form__label" for="planning-notes">{{ __('purchase_planning.fields.notes') }}</label>
            <input
                id="planning-notes"
                type="text"
                class="agricart-planning-form__input"
                wire:model.live="notes"
                placeholder="{{ __('purchase_planning.hints.notes') }}"
                @disabled($this->isReadonly())
            />
        </div>
    </div>

    @unless ($this->isReadonly())
        <div class="agricart-planning-form__search">
            <label class="agricart-planning-form__label" for="planning-product-search">{{ __('purchase_planning.fields.product_search') }}</label>
            <div class="agricart-planning-form__search-row">
                <input
                    id="planning-product-search"
                    type="search"
                    class="agricart-planning-form__input"
                    wire:model.live.debounce.250ms="product_search"
                    wire:keydown.enter.prevent="scanProduct"
                    placeholder="{{ __('purchase_planning.hints.product_search') }}"
                    autocomplete="off"
                />
                <button type="button" class="agricart-planning-form__search-btn" wire:click="scanProduct">
                    {{ __('purchase_planning.actions.add_product') }}
                </button>
            </div>

            @if ($productResults !== [])
                <ul class="agricart-planning-form__results">
                    @foreach ($productResults as $result)
                        <li wire:key="planning-product-{{ $result['id'] }}">
                            <button type="button" class="agricart-planning-form__result" wire:click="addProduct({{ $result['id'] }})">
                                @if ($result['image_url'])
                                    <img src="{{ $result['image_url'] }}" alt="" class="agricart-planning-form__result-thumb" />
                                @endif
                                <span>{{ $result['label'] }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endunless

    @if ($rows === [])
        <div class="agricart-planning-form__empty">
            <p>{{ __('purchase_planning.form.empty') }}</p>
        </div>
    @else
        <div class="agricart-planning-form__grid-wrap">
            <table class="agricart-planning-form__grid">
                <thead>
                    <tr>
                        <th>{{ __('purchase_planning.columns.sr') }}</th>
                        <th>{{ __('purchase_planning.columns.thumb') }}</th>
                        <th>{{ __('purchase_planning.columns.product_name') }}</th>
                        <th>{{ __('purchase_planning.columns.stock') }}</th>
                        <th>{{ __('purchase_planning.columns.required_qty') }}</th>
                        <th>{{ __('purchase_planning.columns.low_stock') }}</th>
                        <th>{{ __('purchase_planning.columns.purchase_qty') }}</th>
                        <th>{{ __('purchase_planning.columns.landing_price') }}</th>
                        <th>{{ __('purchase_planning.columns.sale_price') }}</th>
                        @unless ($this->isReadonly())
                            <th></th>
                        @endunless
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $index => $row)
                        <tr wire:key="planning-row-{{ $index }}-{{ $row['product_id'] ?? $index }}">
                            <td>{{ $index + 1 }}</td>
                            <td>
                                @if ($this->rowImageUrl($row['image_path'] ?? null))
                                    <img
                                        src="{{ $this->rowImageUrl($row['image_path']) }}"
                                        alt=""
                                        class="agricart-planning-form__thumb"
                                    />
                                @else
                                    <span class="agricart-planning-form__thumb-placeholder">—</span>
                                @endif
                            </td>
                            <td class="agricart-planning-form__name">{{ $this->rowDisplayName($row) }}</td>
                            <td>
                                <input
                                    type="text"
                                    class="agricart-planning-form__cell-input"
                                    wire:model.live="rows.{{ $index }}.stock_qty"
                                    @disabled($this->isReadonly())
                                />
                            </td>
                            <td>
                                <input
                                    type="text"
                                    class="agricart-planning-form__cell-input"
                                    wire:model.live="rows.{{ $index }}.required_qty"
                                    @disabled($this->isReadonly())
                                />
                            </td>
                            <td>
                                <input
                                    type="text"
                                    class="agricart-planning-form__cell-input"
                                    wire:model.live="rows.{{ $index }}.low_qty"
                                    @disabled($this->isReadonly())
                                />
                            </td>
                            <td>
                                <input
                                    type="text"
                                    class="agricart-planning-form__cell-input"
                                    wire:model.live="rows.{{ $index }}.purchase_qty"
                                    @disabled($this->isReadonly())
                                />
                            </td>
                            <td>
                                <input
                                    type="text"
                                    class="agricart-planning-form__cell-input"
                                    wire:model.live="rows.{{ $index }}.landing_price"
                                    @disabled($this->isReadonly())
                                />
                            </td>
                            <td>
                                <input
                                    type="text"
                                    class="agricart-planning-form__cell-input"
                                    wire:model.live="rows.{{ $index }}.sale_price"
                                    @disabled($this->isReadonly())
                                />
                            </td>
                            @unless ($this->isReadonly())
                                <td>
                                    <button
                                        type="button"
                                        class="agricart-planning-form__remove"
                                        wire:click="removeRow({{ $index }})"
                                    >
                                        {{ __('purchase_planning.actions.remove') }}
                                    </button>
                                </td>
                            @endunless
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
