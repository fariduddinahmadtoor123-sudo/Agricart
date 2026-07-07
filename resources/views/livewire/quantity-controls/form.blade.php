<div
    class="agricart-quantity-control-form"
    x-data
    x-init="
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('quantity-control-form-busy', {
                detail: { saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:quantity-control-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:quantity-control-form-open.window="$wire.openForm($event.detail.tierId ?? null, $event.detail.mode ?? 'create')"
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

    <p class="agricart-form-panel__hint">{{ __('quantity_controls.hints.form') }}</p>

    <section class="agricart-form-panel__section agricart-user-form__section">
        <div class="agricart-user-form__identity-grid">
            @if ($tierId)
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label">{{ __('quantity_controls.fields.price_type') }}</label>
                    <x-filament::input.wrapper>
                        <input type="text" class="fi-input" value="{{ $linkedPriceLabel }}" readonly />
                    </x-filament::input.wrapper>
                </div>
            @else
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="agricart-quantity-channel-tier-key">
                        {{ __('quantity_controls.fields.price_type') }}
                        <span class="agricart-form-panel__required">*</span>
                    </label>
                    <x-filament::input.wrapper>
                        <select id="agricart-quantity-channel-tier-key" wire:model.live="tier_key" class="fi-input" @disabled($this->isReadonly())>
                            @foreach ($tierTypeOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>
            @endif

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-quantity-channel-name-en">
                    {{ __('quantity_controls.fields.name_en') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-quantity-channel-name-en" type="text" wire:model="name_en" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-quantity-channel-name-ur">{{ __('quantity_controls.fields.name_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="agricart-quantity-channel-name-ur" type="text" wire:model="name_ur" class="fi-input" @disabled($this->isReadonly()) dir="rtl" />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-quantity-channel-min-qty">
                    {{ __('quantity_controls.fields.min_quantity') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-quantity-channel-min-qty" type="number" min="1" step="1" wire:model="min_quantity" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-quantity-channel-status">{{ __('quantity_controls.fields.status') }}</label>
                <x-filament::input.wrapper>
                    <select id="agricart-quantity-channel-status" wire:model="status" class="fi-input" @disabled($this->isReadonly())>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption }}">{{ __('quantity_controls.statuses.' . $statusOption) }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            @if (filled($min_quantity) && filled($tier_key) && is_numeric($min_quantity))
                <div class="agricart-form-panel__field agricart-form-panel__field--full">
                    <div class="agricart-price-controls__preview">
                        {{ __('quantity_controls.hints.quantity_preview', [
                            'qty' => number_format((int) $min_quantity),
                            'price' => $linkedPriceLabel,
                        ]) }}
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>
