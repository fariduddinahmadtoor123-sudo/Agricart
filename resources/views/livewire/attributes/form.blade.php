<div
    class="agricart-attribute-form agricart-packing-unit-form agricart-unit-form agricart-category-form"
    x-data
    x-init="
        $watch('$wire.enriching', (value) => {
            window.dispatchEvent(new CustomEvent('attribute-form-busy', {
                detail: { enriching: !!value, saving: !!$wire.saving },
                bubbles: true,
            }));
        });
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('attribute-form-busy', {
                detail: { enriching: !!$wire.enriching, saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:attribute-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:attribute-form-open.window="$wire.openForm($event.detail.attributeId ?? null, $event.detail.mode ?? 'create')"
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

    <p class="agricart-form-panel__hint agricart-unit-form__intro">{{ __('agricart.attributes.hints.ai_fill') }}</p>

    <div class="agricart-packing-unit-form__grid">
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-attribute-name-en">
                {{ __('agricart.attributes.fields.name_en') }}
                <span class="agricart-form-panel__required">*</span>
            </label>
            <x-filament::input.wrapper>
                <input id="agricart-attribute-name-en" type="text" wire:model.live="name_en" class="fi-input" @disabled($this->isReadonly()) required />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-attribute-name-ur">
                {{ __('agricart.attributes.fields.name_ur') }}
                <span class="agricart-form-panel__required">*</span>
            </label>
            <x-filament::input.wrapper>
                <input id="agricart-attribute-name-ur" type="text" wire:model.live="name_ur" dir="rtl" class="fi-input" @disabled($this->isReadonly()) required />
            </x-filament::input.wrapper>
        </div>
    </div>

    <div class="agricart-unit-form__grid">
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-attribute-code">{{ __('agricart.attributes.fields.code') }}</label>
            <x-filament::input.wrapper>
                <input id="agricart-attribute-code" type="text" value="{{ $code }}" class="fi-input" readonly />
            </x-filament::input.wrapper>
        </div>
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-attribute-slug">{{ __('agricart.attributes.fields.slug') }}</label>
            <x-filament::input.wrapper>
                <input id="agricart-attribute-slug" type="text" value="{{ $slug }}" class="fi-input" readonly />
            </x-filament::input.wrapper>
        </div>
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-attribute-status">{{ __('agricart.attributes.fields.status') }}</label>
            <x-filament::input.wrapper>
                <select id="agricart-attribute-status" wire:model="status" class="fi-select-input" @disabled($this->isReadonly())>
                    <option value="active">{{ __('agricart.form_panel.statuses.active') }}</option>
                    <option value="inactive">{{ __('agricart.form_panel.statuses.inactive') }}</option>
                </select>
            </x-filament::input.wrapper>
        </div>
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-attribute-ai-status">{{ __('agricart.attributes.fields.ai_status') }}</label>
            <x-filament::input.wrapper>
                <input id="agricart-attribute-ai-status" type="text" value="{{ __('agricart.categories.ai_status.'.($ai_status ?: 'none')) }}" class="fi-input" readonly />
            </x-filament::input.wrapper>
        </div>
    </div>
</div>
