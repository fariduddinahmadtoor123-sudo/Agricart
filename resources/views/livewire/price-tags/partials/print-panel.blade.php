<header class="agricart-form-panel__header">
    <div class="agricart-form-panel__header-main">
        <h2 class="agricart-form-panel__title">{{ __('price_tags.panel.title') }}</h2>
        <p class="agricart-form-panel__subtitle">{{ __('price_tags.panel.subtitle') }}</p>
    </div>
    <button
        type="button"
        class="agricart-form-panel__window-btn agricart-form-panel__window-btn--close"
        wire:click="closePrintPanel"
        aria-label="{{ __('price_tags.panel.close') }}"
    >
        <span aria-hidden="true">×</span>
    </button>
</header>

<div class="agricart-form-panel__body agricart-price-tags__modal-body">
    <section class="agricart-form-panel__section agricart-price-tags__label-section">
        <h3 class="agricart-form-panel__section-title">{{ __('price_tags.panel.section_label') }}</h3>
        <div class="agricart-price-tags__label-options">
            @if ($labelProfiles->isNotEmpty())
                <label class="agricart-price-tags__label-field">
                    <span class="agricart-price-tags__label-field-title">{{ __('price_tags.fields.label_size') }}</span>
                    <select wire:model.live="labelProfileKey" class="agricart-price-tags__select">
                        @foreach ($labelProfiles as $profile)
                            <option value="{{ $profile->key }}">{{ $profile->localizedName() }} ({{ $profile->dimensionsLabel() }})</option>
                        @endforeach
                    </select>
                </label>
            @endif

            <label class="agricart-price-tags__label-field">
                <span class="agricart-price-tags__label-field-title">{{ __('price_tags.fields.name_language') }}</span>
                <select wire:model.live="locale" class="agricart-price-tags__select">
                    <option value="en">{{ __('price_tags.locales.en') }}</option>
                    <option value="ur">{{ __('price_tags.locales.ur') }}</option>
                    <option value="both">{{ __('price_tags.locales.both') }}</option>
                </select>
            </label>

            <label class="agricart-price-tags__label-field">
                <span class="agricart-price-tags__label-field-title">{{ __('price_tags.fields.code_type') }}</span>
                <select wire:model.live="codeType" class="agricart-price-tags__select">
                    <option value="barcode">{{ __('price_tags.code_types.barcode') }}</option>
                    <option value="qr">{{ __('price_tags.code_types.qr') }}</option>
                    <option value="none">{{ __('price_tags.code_types.none') }}</option>
                </select>
            </label>
        </div>

        <div class="agricart-price-tags__options-bar">
            <div class="agricart-price-tags__toggle-row">
                <label class="agricart-price-tags__toggle-chip">
                    <input type="checkbox" wire:model.live="showStoreName" />
                    <span>{{ __('price_tags.fields.show_store_name') }}</span>
                </label>
                <label class="agricart-price-tags__toggle-chip">
                    <input type="checkbox" wire:model.live="showProductName" />
                    <span>{{ __('price_tags.fields.show_product_name') }}</span>
                </label>
                <label class="agricart-price-tags__toggle-chip">
                    <input type="checkbox" wire:model.live="showCostPrice" />
                    <span>{{ __('price_tags.fields.show_cost_price') }}</span>
                </label>
                <label class="agricart-price-tags__toggle-chip">
                    <input type="checkbox" wire:model.live="showSalePrice" />
                    <span>{{ __('price_tags.fields.show_sale_price') }}</span>
                </label>
            </div>

            @if ($canCreate)
                <div class="agricart-price-tags__invoice-inline">
                    <input
                        type="text"
                        wire:model="invoiceNumber"
                        wire:keydown.enter.prevent="loadFromInvoice"
                        class="agricart-price-tags__invoice-input"
                        placeholder="{{ __('price_tags.fields.invoice_placeholder') }}"
                        aria-label="{{ __('price_tags.fields.invoice_number') }}"
                    />
                    <button type="button" class="agricart-price-tags__btn is-primary" wire:click="loadFromInvoice">{{ __('price_tags.actions.load_invoice') }}</button>
                </div>
            @endif
        </div>

        @if ($canCreate)
            <div class="agricart-price-tags__product-search">
                <div class="agricart-price-tags__product-search-row">
                    <label class="agricart-price-tags__product-search-label" for="price-tag-product-search">
                        {{ __('price_tags.fields.add_product') }}
                    </label>
                    <input
                        id="price-tag-product-search"
                        type="search"
                        wire:model.live.debounce.300ms="productQuery"
                        wire:keydown.enter.prevent="scanProduct"
                        class="agricart-price-tags__product-search-input"
                        placeholder="{{ __('price_tags.fields.add_product_placeholder') }}"
                        autocomplete="off"
                    />
                    <button type="button" class="agricart-price-tags__product-search-btn" wire:click="scanProduct">
                        {{ __('price_tags.actions.scan_add') }}
                    </button>
                </div>

                @if ($productResults !== [])
                    <ul class="agricart-price-tags__search-results">
                        @foreach ($productResults as $result)
                            <li>
                                <button type="button" wire:click="addProduct({{ $result['id'] }})">
                                    @if (filled($result['image_url'] ?? null))
                                        <img src="{{ $result['image_url'] }}" alt="" />
                                    @endif
                                    <span>{{ $result['label'] }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </section>

    <section class="agricart-form-panel__section">
        <div class="agricart-price-tags__queue-head">
            <h3 class="agricart-form-panel__section-title">{{ __('price_tags.panel.section_queue') }}</h3>
            <p>{{ __('price_tags.messages.queue_count', ['count' => $queueCount]) }}</p>
            @if ($canCreate && $queueCount > 0)
                <button type="button" class="agricart-price-tags__btn" wire:click="clearLines">{{ __('price_tags.actions.clear_all') }}</button>
            @endif
        </div>

        @if ($filteredLines->isEmpty())
            <div class="agricart-price-tags__queue-empty">{{ __('price_tags.messages.queue_empty') }}</div>
        @else
            <div class="agricart-price-tags__queue-grid">
                @foreach ($filteredLines as $line)
                    @php
                        $tag = $this->tagPreview($line);
                        $product = $line['product'] ?? null;
                    @endphp
                    <div class="agricart-price-tags__queue-item" wire:key="price-tag-line-{{ $line['line_id'] }}">
                        <div class="agricart-price-tags__queue-item-head">
                            <div>
                                <strong>{{ $product?->name_en ?? '—' }}</strong>
                                @if (filled($product?->name_ur))
                                    <p dir="rtl">{{ $product->name_ur }}</p>
                                @endif
                            </div>
                            @if ($canCreate)
                                <button type="button" class="agricart-price-tags__btn is-danger" wire:click="removeLine('{{ $line['line_id'] }}')">{{ __('price_tags.actions.remove') }}</button>
                            @endif
                        </div>
                        <div class="agricart-price-tags__queue-item-body">
                            <div class="agricart-price-tags__preview-wrap">
                                @include('livewire.price-tags.partials.tag-sticker', ['tag' => $tag, 'preview' => true])
                            </div>
                            <div class="agricart-price-tags__item-controls">
                                <label class="agricart-price-tags__field">
                                    <span>{{ __('price_tags.fields.labels_qty') }}</span>
                                    <input type="number" min="1" step="1" class="fi-input" wire:model.live="lines.{{ $line['_index'] }}.qty" @disabled(! $canCreate) />
                                </label>
                                <label class="agricart-price-tags__field">
                                    <span>{{ __('price_tags.fields.cost_price') }}</span>
                                    <input type="text" class="fi-input" wire:model.live="lines.{{ $line['_index'] }}.cost_price" @disabled(! $canCreate) />
                                </label>
                                <label class="agricart-price-tags__field">
                                    <span>{{ __('price_tags.fields.sale_price') }}</span>
                                    <input type="text" class="fi-input" wire:model.live="lines.{{ $line['_index'] }}.sale_price" @disabled(! $canCreate) />
                                </label>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>

<footer class="agricart-form-panel__footer">
    <div class="agricart-form-panel__footer-inner agricart-price-tags__modal-actions">
        <button type="button" class="agricart-price-tags__btn" wire:click="closePrintPanel">{{ __('price_tags.panel.close') }}</button>
        <button type="button" class="agricart-price-tags__btn is-primary" wire:click="printBatch">{{ __('price_tags.panel.print_now') }}</button>
    </div>
</footer>
