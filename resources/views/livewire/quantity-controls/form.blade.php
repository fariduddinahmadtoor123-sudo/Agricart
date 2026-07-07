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
    x-on:quantity-control-form-open.window="$wire.openForm($event.detail.quantityControlId ?? null, $event.detail.mode ?? 'create')"
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

    <p class="agricart-form-panel__hint">{{ __('quantity_controls.hints.scaffold') }}</p>

    <section class="agricart-form-panel__section agricart-user-form__section">
        <div class="agricart-user-form__identity-grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-quantity-control-number">{{ __('quantity_controls.fields.number') }}</label>
                <x-filament::input.wrapper>
                    <input id="agricart-quantity-control-number" type="text" wire:model="control_number" class="fi-input" readonly />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-quantity-control-status">{{ __('quantity_controls.fields.status') }}</label>
                <x-filament::input.wrapper>
                    <select id="agricart-quantity-control-status" wire:model="status" class="fi-input" @disabled($this->isReadonly())>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption }}">{{ __('quantity_controls.statuses.' . $statusOption) }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-quantity-control-name-en">
                    {{ __('quantity_controls.fields.name_en') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-quantity-control-name-en" type="text" wire:model="name_en" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-quantity-control-name-ur">{{ __('quantity_controls.fields.name_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="agricart-quantity-control-name-ur" type="text" wire:model="name_ur" class="fi-input" @disabled($this->isReadonly()) dir="rtl" />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field agricart-form-panel__field--full">
                <label class="agricart-form-panel__label" for="agricart-quantity-control-notes">{{ __('quantity_controls.fields.notes') }}</label>
                <x-filament::input.wrapper>
                    <textarea id="agricart-quantity-control-notes" rows="3" wire:model="notes" class="fi-input agricart-form-panel__textarea" @disabled($this->isReadonly())></textarea>
                </x-filament::input.wrapper>
            </div>
        </div>
    </section>
</div>
