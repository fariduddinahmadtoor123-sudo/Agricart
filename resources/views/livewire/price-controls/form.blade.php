<div
    class="agricart-price-control-form"
    x-data
    x-init="
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('price-control-form-busy', {
                detail: { saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:price-control-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:price-control-form-open.window="$wire.openForm($event.detail.tierId ?? null, $event.detail.mode ?? 'create')"
>
    @if ($statusMessage)
        <div @class([
            'agricart-user-form__alert',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
            'is-warning' => $statusType === 'warning',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <p class="agricart-form-panel__hint">
        {{ $isRetailTier ? __('price_controls.hints.form_retail') : __('price_controls.hints.form') }}
    </p>

    <section class="agricart-form-panel__section agricart-user-form__section">
        <div class="agricart-user-form__identity-grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-price-channel-name-en">
                    {{ __('price_controls.fields.name_en') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-price-channel-name-en" type="text" wire:model="name_en" class="fi-input" @disabled($this->isReadonly() || $isRetailTier) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-price-channel-name-ur">{{ __('price_controls.fields.name_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="agricart-price-channel-name-ur" type="text" wire:model="name_ur" class="fi-input" @disabled($this->isReadonly() || $isRetailTier) dir="rtl" />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-price-channel-markup">
                    {{ __('price_controls.fields.markup_percent') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-price-channel-markup" type="number" min="0" max="500" step="0.001" wire:model.live="markup_percent" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-price-channel-status">{{ __('price_controls.fields.status') }}</label>
                <x-filament::input.wrapper>
                    <select id="agricart-price-channel-status" wire:model="status" class="fi-input" @disabled($this->isReadonly())>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption }}">{{ __('price_controls.statuses.' . $statusOption) }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field agricart-form-panel__field--full">
                <label class="agricart-form-panel__label" for="agricart-price-channel-product-field">{{ __('price_controls.fields.product_field') }}</label>
                @if ($isRetailTier || $usesCustomField)
                    <x-filament::input.wrapper>
                        <input type="text" class="fi-input" value="{{ $productFieldLabel }}" readonly />
                    </x-filament::input.wrapper>
                    @if ($isRetailTier)
                        <p class="agricart-form-panel__field-hint">{{ __('price_controls.hints.retail_field') }}</p>
                    @else
                        <p class="agricart-form-panel__field-hint">{{ __('price_controls.hints.custom_field') }}</p>
                    @endif
                @else
                    <x-filament::input.wrapper>
                        <select id="agricart-price-channel-product-field" wire:model="product_field" class="fi-input" @disabled($this->isReadonly())>
                            @foreach ($productFieldOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                @endif
            </div>

            @if ($previewPrice)
                <div class="agricart-form-panel__field agricart-form-panel__field--full">
                    <div class="agricart-price-controls__preview">
                        {{ __('price_controls.hints.example_preview', [
                            'base' => $exampleBaseFormatted,
                            'price' => $previewPrice,
                        ]) }}
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>
