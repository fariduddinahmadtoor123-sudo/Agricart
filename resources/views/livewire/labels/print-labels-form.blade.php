<div
    class="agricart-label-print-form"
    x-data
    x-on:label-print-form-open.window="$wire.openForm()"
>
    <form
        method="POST"
        action="{{ route('admin.labels.batch-print') }}"
        target="_blank"
        class="agricart-label-print-form__layout"
    >
        @csrf

        <section class="agricart-label-print-form__section">
            <div class="agricart-label-print-form__section-head">
                <div>
                    <h3>{{ __('agricart.labels.batch.select_products') }}</h3>
                    <p>{{ __('agricart.labels.batch.select_products_hint') }}</p>
                </div>
                <span class="agricart-label-print-form__count">
                    {{ trans_choice('agricart.labels.batch.selected_count', $this->selectedProducts->count(), ['count' => $this->selectedProducts->count()]) }}
                </span>
            </div>

            <label class="agricart-form-panel__label" for="agricart-label-product-search">
                {{ __('agricart.labels.batch.search_label') }}
            </label>
            <x-filament::input.wrapper>
                <input
                    id="agricart-label-product-search"
                    type="search"
                    wire:model.live.debounce.250ms="search"
                    class="fi-input"
                    placeholder="{{ __('agricart.labels.batch.search_placeholder') }}"
                    autocomplete="off"
                />
            </x-filament::input.wrapper>

            @if (filled(trim($search)))
                <div class="agricart-label-print-form__results">
                    @forelse ($this->searchResults as $product)
                        <button
                            type="button"
                            wire:click="addProduct({{ $product->id }})"
                            class="agricart-label-print-form__result"
                        >
                            <span>
                                <strong>{{ $product->name_en }}</strong>
                                @if (filled($product->name_ur))
                                    <small dir="rtl">{{ $product->name_ur }}</small>
                                @endif
                            </span>
                            <code>{{ $product->label_code }}</code>
                        </button>
                    @empty
                        <p class="agricart-label-print-form__empty">{{ __('agricart.labels.batch.no_results') }}</p>
                    @endforelse
                </div>
            @endif
        </section>

        <section class="agricart-label-print-form__section">
            <div class="agricart-label-print-form__settings">
                <label>
                    <span class="agricart-form-panel__label">{{ __('agricart.labels.print.profile_label') }}</span>
                    <select name="profile" wire:model="profileKey" class="agricart-label-print-form__select" required>
                        @foreach ($profiles as $profile)
                            <option value="{{ $profile->key }}">
                                {{ $profile->localizedName() }} ({{ $profile->dimensionsLabel() }})
                            </option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="agricart-form-panel__label">{{ __('agricart.labels.batch.language_label') }}</span>
                    <select name="language" wire:model.live="labelLanguage" class="agricart-label-print-form__select" required>
                        <option value="en">{{ __('agricart.labels.batch.languages.en') }}</option>
                        <option value="ur">{{ __('agricart.labels.batch.languages.ur') }}</option>
                        <option value="both">{{ __('agricart.labels.batch.languages.both') }}</option>
                    </select>
                </label>
            </div>

            <fieldset class="agricart-label-print-form__fields">
                <legend>{{ __('agricart.labels.batch.label_information') }}</legend>
                <p>{{ __('agricart.labels.batch.label_information_hint') }}</p>
                <div class="agricart-label-print-form__field-grid">
                    @foreach ($fieldOptions as $key => $label)
                        <label class="agricart-label-print-form__check">
                            <input
                                type="checkbox"
                                name="fields[]"
                                value="{{ $key }}"
                                wire:model="fields"
                                @disabled($key === 'product_name')
                            />
                            @if ($key === 'product_name')
                                <input type="hidden" name="fields[]" value="product_name" />
                            @endif
                            <span>
                                {{ $label }}
                                @if ($key === 'store_name' && filled($storeNames[$labelLanguage === 'ur' ? 'ur' : 'en'] ?? null))
                                    <small class="agricart-label-print-form__field-value" @if ($labelLanguage === 'ur') dir="rtl" @endif>
                                        {{ $storeNames[$labelLanguage === 'ur' ? 'ur' : 'en'] }}
                                    </small>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </section>

        <section class="agricart-label-print-form__section">
            <div class="agricart-label-print-form__section-head">
                <div>
                    <h3>{{ __('agricart.labels.batch.print_queue') }}</h3>
                    <p>{{ __('agricart.labels.batch.print_queue_hint') }}</p>
                </div>
            </div>

            <div class="agricart-label-print-form__queue">
                @forelse ($this->selectedProducts as $product)
                    <article class="agricart-label-print-form__product" wire:key="print-label-product-{{ $product->id }}">
                        <input type="hidden" name="items[{{ $product->id }}][product_id]" value="{{ $product->id }}" />
                        <header>
                            <div>
                                <h4>{{ $product->name_en }}</h4>
                                <p><code>{{ $product->label_code }}</code> · {{ $product->code }}</p>
                            </div>
                            <span class="agricart-label-print-form__language-badge">
                                {{ __('agricart.labels.batch.languages.'.$labelLanguage) }}
                            </span>
                            <button
                                type="button"
                                wire:click="removeProduct({{ $product->id }})"
                                class="agricart-label-print-form__remove"
                            >
                                {{ __('agricart.labels.batch.remove') }}
                            </button>
                        </header>

                        <div class="agricart-label-print-form__variants">
                            @foreach ($variantOptions as $variant => $label)
                                <div class="agricart-label-print-form__variant">
                                    <label class="agricart-label-print-form__check">
                                        <input
                                            type="checkbox"
                                            name="items[{{ $product->id }}][enabled][]"
                                            value="{{ $variant }}"
                                            wire:model="enabledVariants.{{ $product->id }}"
                                        />
                                        <span>{{ $label }}</span>
                                    </label>
                                    <label>
                                        <span>{{ __('agricart.labels.batch.copies') }}</span>
                                        <input
                                            type="number"
                                            name="items[{{ $product->id }}][copies][{{ $variant }}]"
                                            wire:model="copies.{{ $product->id }}.{{ $variant }}"
                                            min="1"
                                            max="100"
                                            inputmode="numeric"
                                            class="agricart-label-print-form__copies"
                                        />
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <p class="agricart-label-print-form__empty agricart-label-print-form__empty--queue">
                        {{ __('agricart.labels.batch.empty_queue') }}
                    </p>
                @endforelse
            </div>
        </section>

        <button
            id="agricart-label-batch-submit"
            type="submit"
            class="fi-sr-only"
            @disabled($this->selectedProducts->isEmpty())
        >
            {{ __('agricart.labels.print.submit_button') }}
        </button>
    </form>
</div>
