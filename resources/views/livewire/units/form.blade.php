<div
    class="agricart-unit-form agricart-category-form"
    x-data
    x-init="
        $watch('$wire.enriching', (value) => {
            window.dispatchEvent(new CustomEvent('unit-form-busy', {
                detail: { enriching: !!value, saving: !!$wire.saving },
                bubbles: true,
            }));
        });
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('unit-form-busy', {
                detail: { enriching: !!$wire.enriching, saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:unit-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:unit-form-open.window="$wire.openForm($event.detail.unitId ?? null, $event.detail.mode ?? 'create')"
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

    <p class="agricart-form-panel__hint agricart-unit-form__intro">{{ __('agricart.units.hints.ai_fill') }}</p>

    <div class="agricart-unit-form__grid">
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-unit-name-en">
                {{ __('agricart.units.fields.name_en') }}
                <span class="agricart-form-panel__required">*</span>
            </label>
            <x-filament::input.wrapper>
                <input id="agricart-unit-name-en" type="text" wire:model="name_en" class="fi-input" @disabled($this->isReadonly()) required />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-unit-abbr-en">
                {{ __('agricart.units.fields.abbreviation_en') }}
                <span class="agricart-form-panel__required">*</span>
            </label>
            <x-filament::input.wrapper>
                <input id="agricart-unit-abbr-en" type="text" wire:model="abbreviation_en" class="fi-input" @disabled($this->isReadonly()) required />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-unit-name-ur">
                {{ __('agricart.units.fields.name_ur') }}
                <span class="agricart-form-panel__required">*</span>
            </label>
            <x-filament::input.wrapper>
                <input id="agricart-unit-name-ur" type="text" wire:model="name_ur" dir="rtl" class="fi-input" @disabled($this->isReadonly()) required />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-unit-abbr-ur">
                {{ __('agricart.units.fields.abbreviation_ur') }}
                <span class="agricart-form-panel__required">*</span>
            </label>
            <x-filament::input.wrapper>
                <input id="agricart-unit-abbr-ur" type="text" wire:model="abbreviation_ur" dir="rtl" class="fi-input" @disabled($this->isReadonly()) required />
            </x-filament::input.wrapper>
        </div>
    </div>

    <div class="agricart-unit-form__grid">
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-unit-code">{{ __('agricart.units.fields.code') }}</label>
            <x-filament::input.wrapper>
                <input id="agricart-unit-code" type="text" value="{{ $code }}" class="fi-input" readonly />
            </x-filament::input.wrapper>
        </div>
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-unit-slug">{{ __('agricart.units.fields.slug') }}</label>
            <x-filament::input.wrapper>
                <input id="agricart-unit-slug" type="text" value="{{ $slug }}" class="fi-input" readonly />
            </x-filament::input.wrapper>
        </div>
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-unit-status">{{ __('agricart.units.fields.status') }}</label>
            <x-filament::input.wrapper>
                <select id="agricart-unit-status" wire:model="status" class="fi-select-input" @disabled($this->isReadonly())>
                    <option value="active">{{ __('agricart.form_panel.statuses.active') }}</option>
                    <option value="inactive">{{ __('agricart.form_panel.statuses.inactive') }}</option>
                </select>
            </x-filament::input.wrapper>
        </div>
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-unit-ai-status">{{ __('agricart.units.fields.ai_status') }}</label>
            <x-filament::input.wrapper>
                <input id="agricart-unit-ai-status" type="text" value="{{ __('agricart.categories.ai_status.'.($ai_status ?: 'none')) }}" class="fi-input" readonly />
            </x-filament::input.wrapper>
        </div>
    </div>
</div>
