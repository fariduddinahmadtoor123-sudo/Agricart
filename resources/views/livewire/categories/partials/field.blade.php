@php
    $base = $field['base'] ?? null;
    $rows = $field['rows'] ?? 4;
@endphp

@if (($field['type'] ?? '') === 'bilingual_text')
    <div class="agricart-form-panel__field agricart-form-panel__field--full agricart-category-form__bilingual">
        <span class="agricart-category-form__bilingual-label">{{ $field['label'] }}</span>
        <div class="agricart-form-panel__fields agricart-form-panel__fields--pair">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.categories.labels.english') }}</label>
                <x-filament::input.wrapper>
                    <input type="text" wire:model="{{ $base }}_en" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.categories.labels.urdu') }}</label>
                <x-filament::input.wrapper>
                    <input type="text" wire:model="{{ $base }}_ur" dir="rtl" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>
        </div>
    </div>
@elseif (($field['type'] ?? '') === 'bilingual_textarea')
    <div class="agricart-form-panel__field agricart-form-panel__field--full agricart-category-form__bilingual">
        <span class="agricart-category-form__bilingual-label">{{ $field['label'] }}</span>
        <div class="agricart-form-panel__fields agricart-form-panel__fields--pair">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.categories.labels.english') }}</label>
                <x-filament::input.wrapper>
                    <textarea rows="{{ $rows }}" wire:model="{{ $base }}_en" class="fi-input agricart-form-panel__textarea" @disabled($readonly)></textarea>
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.categories.labels.urdu') }}</label>
                <x-filament::input.wrapper>
                    <textarea rows="{{ $rows }}" wire:model="{{ $base }}_ur" dir="rtl" class="fi-input agricart-form-panel__textarea" @disabled($readonly)></textarea>
                </x-filament::input.wrapper>
            </div>
        </div>
    </div>
@elseif (($field['type'] ?? '') === 'json')
    <div class="agricart-form-panel__field agricart-form-panel__field--full">
        <label class="agricart-form-panel__label">{{ $field['label'] }}</label>
        <p class="agricart-form-panel__hint agricart-category-form__json-hint">{{ __('agricart.categories.hints.search_terms') }}</p>
        <x-filament::input.wrapper>
            <textarea rows="3" wire:model="search_terms_json" class="fi-input agricart-form-panel__textarea agricart-category-form__json-input" @disabled($readonly)></textarea>
        </x-filament::input.wrapper>
    </div>
@elseif (($field['type'] ?? '') === 'text')
    <div class="agricart-form-panel__field">
        <label class="agricart-form-panel__label">{{ $field['label'] }}</label>
        <x-filament::input.wrapper>
            <input type="text" wire:model="{{ $field['name'] }}" class="fi-input" @disabled($readonly) />
        </x-filament::input.wrapper>
    </div>
@endif
