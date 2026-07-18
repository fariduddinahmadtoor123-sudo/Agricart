<div
    class="agricart-packing-unit-form agricart-unit-form agricart-category-form"
    x-data
    x-init="
        $watch('$wire.enriching', (value) => {
            window.dispatchEvent(new CustomEvent('packing-unit-form-busy', {
                detail: { enriching: !!value, saving: !!$wire.saving },
                bubbles: true,
            }));
        });
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('packing-unit-form-busy', {
                detail: { enriching: !!$wire.enriching, saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:packing-unit-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:packing-unit-form-open.window="$wire.openForm($event.detail.packingUnitId ?? null, $event.detail.mode ?? 'create')"
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

    <p class="agricart-form-panel__hint agricart-unit-form__intro">{{ __('agricart.packing_units.hints.ai_fill') }}</p>

    <div class="agricart-packing-unit-form__grid">
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-packing-unit-name-en">
                {{ __('agricart.packing_units.fields.name_en') }}
                <span class="agricart-form-panel__required">*</span>
            </label>
            <x-filament::input.wrapper>
                <input id="agricart-packing-unit-name-en" type="text" wire:model.live="name_en" class="fi-input" @disabled($this->isReadonly()) required />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-packing-unit-name-ur">
                {{ __('agricart.packing_units.fields.name_ur') }}
                <span class="agricart-form-panel__required">*</span>
            </label>
            <x-filament::input.wrapper>
                <input id="agricart-packing-unit-name-ur" type="text" wire:model.live="name_ur" dir="rtl" class="fi-input" @disabled($this->isReadonly()) required />
            </x-filament::input.wrapper>
        </div>
    </div>

    <div class="agricart-unit-form__grid">
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-packing-unit-code">{{ __('agricart.packing_units.fields.code') }}</label>
            <x-filament::input.wrapper>
                <input id="agricart-packing-unit-code" type="text" value="{{ $code }}" class="fi-input" readonly />
            </x-filament::input.wrapper>
        </div>
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-packing-unit-slug">{{ __('agricart.packing_units.fields.slug') }}</label>
            <x-filament::input.wrapper>
                <input id="agricart-packing-unit-slug" type="text" value="{{ $slug }}" class="fi-input" readonly />
            </x-filament::input.wrapper>
        </div>
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-packing-unit-status">{{ __('agricart.packing_units.fields.status') }}</label>
            <x-filament::input.wrapper>
                <select id="agricart-packing-unit-status" wire:model="status" class="fi-select-input" @disabled($this->isReadonly())>
                    <option value="active">{{ __('agricart.form_panel.statuses.active') }}</option>
                    <option value="inactive">{{ __('agricart.form_panel.statuses.inactive') }}</option>
                </select>
            </x-filament::input.wrapper>
        </div>
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-packing-unit-ai-status">{{ __('agricart.packing_units.fields.ai_status') }}</label>
            <x-filament::input.wrapper>
                <input id="agricart-packing-unit-ai-status" type="text" value="{{ __('agricart.categories.ai_status.'.($ai_status ?: 'none')) }}" class="fi-input" readonly />
            </x-filament::input.wrapper>
        </div>
    </div>
</div>
