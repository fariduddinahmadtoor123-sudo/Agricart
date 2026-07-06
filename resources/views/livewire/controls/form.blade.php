<div
    class="agricart-control-form agricart-unit-form agricart-category-form"
    x-data
    x-init="
        $watch('$wire.enriching', (value) => {
            window.dispatchEvent(new CustomEvent('control-form-busy', {
                detail: { enriching: !!value, saving: !!$wire.saving },
                bubbles: true,
            }));
        });
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('control-form-busy', {
                detail: { enriching: !!$wire.enriching, saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:control-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:control-form-open.window="$wire.openForm($event.detail.controlId ?? null, $event.detail.mode ?? 'create')"
>
    @if ($statusMessage)
        <div @class([
            'agricart-category-form__alert',
            'agricart-category-form__alert--prominent' => $statusType === 'success' && $ai_status === 'enriched',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
            'is-warning' => $statusType === 'warning',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <p class="agricart-form-panel__hint agricart-unit-form__intro">{{ __('agricart.controls.hints.ai_fill') }}</p>

    <div class="agricart-control-form__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.controls.sections.names') }}</h3>
        <div class="agricart-packing-unit-form__grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-control-name-en">
                    {{ __('agricart.controls.fields.name_en') }}
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-control-name-en" type="text" wire:model.live="name_en" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-control-name-ur">
                    {{ __('agricart.controls.fields.name_ur') }}
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-control-name-ur" type="text" wire:model.live="name_ur" dir="rtl" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>
        </div>
    </div>

    <div class="agricart-control-form__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.controls.sections.values') }}</h3>
        <div class="agricart-packing-unit-form__grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-control-value-en">
                    {{ __('agricart.controls.fields.value_en') }}
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-control-value-en" type="text" wire:model.live="value_en" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-control-value-ur">
                    {{ __('agricart.controls.fields.value_ur') }}
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-control-value-ur" type="text" wire:model.live="value_ur" dir="rtl" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>
        </div>
    </div>
</div>
