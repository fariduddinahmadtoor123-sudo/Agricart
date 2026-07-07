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
    x-on:price-control-form-open.window="$wire.openForm($event.detail.priceControlId ?? null, $event.detail.mode ?? 'create')"
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

    <p class="agricart-form-panel__hint">{{ __('price_controls.hints.scaffold') }}</p>

    <section class="agricart-form-panel__section agricart-user-form__section">
        <div class="agricart-user-form__identity-grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-price-control-number">{{ __('price_controls.fields.number') }}</label>
                <x-filament::input.wrapper>
                    <input id="agricart-price-control-number" type="text" wire:model="control_number" class="fi-input" readonly />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-price-control-status">{{ __('price_controls.fields.status') }}</label>
                <x-filament::input.wrapper>
                    <select id="agricart-price-control-status" wire:model="status" class="fi-input" @disabled($this->isReadonly())>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption }}">{{ __('price_controls.statuses.' . $statusOption) }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-price-control-name-en">
                    {{ __('price_controls.fields.name_en') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-price-control-name-en" type="text" wire:model="name_en" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-price-control-name-ur">{{ __('price_controls.fields.name_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="agricart-price-control-name-ur" type="text" wire:model="name_ur" class="fi-input" @disabled($this->isReadonly()) dir="rtl" />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field agricart-form-panel__field--full">
                <label class="agricart-form-panel__label" for="agricart-price-control-notes">{{ __('price_controls.fields.notes') }}</label>
                <x-filament::input.wrapper>
                    <textarea id="agricart-price-control-notes" rows="3" wire:model="notes" class="fi-input agricart-form-panel__textarea" @disabled($this->isReadonly())></textarea>
                </x-filament::input.wrapper>
            </div>
        </div>
    </section>
</div>
