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
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-control-name-en" type="text" wire:model.live="name_en" class="fi-input" @disabled($this->isReadonly()) required />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-control-name-ur">
                    {{ __('agricart.controls.fields.name_ur') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-control-name-ur" type="text" wire:model.live="name_ur" dir="rtl" class="fi-input" @disabled($this->isReadonly()) required />
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
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <textarea id="agricart-control-value-en" rows="3" wire:model.live="value_en" class="fi-input agricart-form-panel__textarea" @disabled($this->isReadonly()) required></textarea>
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-control-value-ur">
                    {{ __('agricart.controls.fields.value_ur') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <textarea id="agricart-control-value-ur" rows="3" wire:model.live="value_ur" dir="rtl" class="fi-input agricart-form-panel__textarea" @disabled($this->isReadonly()) required></textarea>
                </x-filament::input.wrapper>
            </div>
        </div>
    </div>

    <div class="agricart-unit-form__grid">
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-control-code">{{ __('agricart.controls.fields.code') }}</label>
            <x-filament::input.wrapper>
                <input id="agricart-control-code" type="text" value="{{ $code }}" class="fi-input" readonly />
            </x-filament::input.wrapper>
        </div>
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-control-slug">{{ __('agricart.controls.fields.slug') }}</label>
            <x-filament::input.wrapper>
                <input id="agricart-control-slug" type="text" value="{{ $slug }}" class="fi-input" readonly />
            </x-filament::input.wrapper>
        </div>
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-control-status">{{ __('agricart.controls.fields.status') }}</label>
            <x-filament::input.wrapper>
                <select id="agricart-control-status" wire:model="status" class="fi-select-input" @disabled($this->isReadonly())>
                    <option value="active">{{ __('agricart.form_panel.statuses.active') }}</option>
                    <option value="inactive">{{ __('agricart.form_panel.statuses.inactive') }}</option>
                </select>
            </x-filament::input.wrapper>
        </div>
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="agricart-control-ai-status">{{ __('agricart.controls.fields.ai_status') }}</label>
            <x-filament::input.wrapper>
                <input id="agricart-control-ai-status" type="text" value="{{ __('agricart.categories.ai_status.'.($ai_status ?: 'none')) }}" class="fi-input" readonly />
            </x-filament::input.wrapper>
        </div>
    </div>
</div>
