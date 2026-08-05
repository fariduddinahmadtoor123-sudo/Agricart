@php
    $readonly = $this->isReadonly();
@endphp

<div
    class="agricart-form-panel__fields agricart-contact-message-template-form"
    x-data
    x-init="
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('contact-message-template-form-busy', {
                detail: { saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:contact-message-template-form-open.window="$wire.openForm($event.detail?.templateId ?? $event.detail?.[0]?.templateId ?? null, $event.detail?.mode ?? $event.detail?.[0]?.mode ?? 'create')"
    x-on:contact-message-template-form-action.window="$wire.handlePanelAction($event.detail?.action ?? $event.detail?.[0]?.action)"
>
    @if ($statusMessage)
        <div @class([
            'agricart-form-panel__alert',
            'is-success' => $statusType === 'success',
            'is-error' => $statusType === 'error' || $statusType === 'danger',
        ]) role="status">
            {{ $statusMessage }}
        </div>
    @endif

    <p class="agricart-form-panel__hint">{{ __('contact_message_templates.placeholder_hint') }}</p>

    <div class="agricart-form-panel__fields agricart-form-panel__fields--pair">
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="cmt-form-name-en">
                {{ __('contact_message_templates.fields.name_en') }}
                <span class="agricart-form-panel__required">*</span>
            </label>
            <x-filament::input.wrapper>
                <input id="cmt-form-name-en" type="text" wire:model="name_en" class="fi-input" maxlength="120" @disabled($readonly) />
            </x-filament::input.wrapper>
            @error('name_en') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="cmt-form-name-ur">
                {{ __('contact_message_templates.fields.name_ur') }}
            </label>
            <x-filament::input.wrapper>
                <input id="cmt-form-name-ur" type="text" wire:model="name_ur" class="fi-input" maxlength="120" dir="rtl" @disabled($readonly) />
            </x-filament::input.wrapper>
            @error('name_ur') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="agricart-form-panel__fields agricart-form-panel__fields--pair">
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="cmt-form-subject-en">
                {{ __('contact_message_templates.fields.subject_en') }}
                <span class="agricart-form-panel__required">*</span>
            </label>
            <x-filament::input.wrapper>
                <input id="cmt-form-subject-en" type="text" wire:model="subject_en" class="fi-input" maxlength="200" @disabled($readonly) />
            </x-filament::input.wrapper>
            @error('subject_en') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="cmt-form-subject-ur">
                {{ __('contact_message_templates.fields.subject_ur') }}
            </label>
            <x-filament::input.wrapper>
                <input id="cmt-form-subject-ur" type="text" wire:model="subject_ur" class="fi-input" maxlength="200" dir="rtl" @disabled($readonly) />
            </x-filament::input.wrapper>
            @error('subject_ur') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="agricart-form-panel__field agricart-form-panel__field--full">
        <label class="agricart-form-panel__label" for="cmt-form-body-en">
            {{ __('contact_message_templates.fields.body_en') }}
            <span class="agricart-form-panel__required">*</span>
        </label>
        <x-filament::input.wrapper>
            <textarea id="cmt-form-body-en" rows="10" wire:model="body_en" class="fi-input agricart-form-panel__textarea" maxlength="5000" @disabled($readonly)></textarea>
        </x-filament::input.wrapper>
        @error('body_en') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>

    <div class="agricart-form-panel__field agricart-form-panel__field--full">
        <label class="agricart-form-panel__label" for="cmt-form-body-ur">
            {{ __('contact_message_templates.fields.body_ur') }}
        </label>
        <x-filament::input.wrapper>
            <textarea id="cmt-form-body-ur" rows="10" wire:model="body_ur" class="fi-input agricart-form-panel__textarea" maxlength="5000" dir="rtl" @disabled($readonly)></textarea>
        </x-filament::input.wrapper>
        @error('body_ur') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>

    <label class="agricart-form-panel__label" style="display:flex;gap:0.5rem;align-items:center;">
        <input type="checkbox" wire:model="is_active" @disabled($readonly) />
        <span>{{ __('contact_message_templates.fields.active') }}</span>
    </label>
</div>
