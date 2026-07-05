@php
    use Filament\Support\Icons\Heroicon;
@endphp

<div class="agricart-ai-form">
    <div class="agricart-ai-form__intro">
        <x-filament::icon :icon="Heroicon::OutlinedSparkles" class="agricart-ai-form__intro-icon" />
        <div>
            <p class="agricart-ai-form__intro-title">{{ __('agricart.ai.form.intro_title') }}</p>
            <p class="agricart-ai-form__intro-text">
                {{ __('agricart.ai.form.intro_text') }}
            </p>
        </div>
    </div>

    <div class="agricart-ai-form__fields">
        <div class="agricart-ai-form__field">
            <label class="agricart-ai-form__label" for="ai-provider">{{ __('agricart.ai.form.provider') }}</label>
            <select id="ai-provider" wire:model.live="provider" class="agricart-ai-form__select">
                @foreach ($providerOptions as $option)
                    <option value="{{ $option['value'] }}" @disabled(! $option['enabled'])>
                        {{ $option['label'] }}@unless ($option['enabled']) {{ __('agricart.ai.coming_soon') }} @endunless
                    </option>
                @endforeach
            </select>
        </div>

        <div class="agricart-ai-form__field">
            <label class="agricart-ai-form__label" for="ai-api-key">{{ __('agricart.ai.form.api_key') }}</label>
            <input
                id="ai-api-key"
                type="password"
                wire:model.live="apiKey"
                class="agricart-ai-form__input"
                placeholder="{{ __('agricart.ai.form.api_key_placeholder') }}"
                autocomplete="off"
            />
            @error('apiKey')
                <p class="agricart-ai-form__field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="agricart-ai-form__field agricart-ai-form__field--model">
            <label class="agricart-ai-form__label" for="ai-model-search">{{ __('agricart.ai.form.model') }}</label>

            <div class="agricart-ai-form__model-search-wrap">
                <x-filament::icon :icon="Heroicon::OutlinedMagnifyingGlass" class="agricart-ai-form__model-search-icon" />
                <input
                    id="ai-model-search"
                    type="search"
                    wire:model.live="modelSearch"
                    class="agricart-ai-form__input agricart-ai-form__input--search"
                    placeholder="{{ __('agricart.ai.form.model_search_placeholder') }}"
                    @disabled(! $modelsLoaded)
                />
            </div>

            <div class="agricart-ai-form__model-actions">
                <x-filament::button
                    color="gray"
                    outlined
                    tag="button"
                    type="button"
                    wire:click="loadModels"
                    wire:loading.attr="disabled"
                    wire:target="loadModels"
                >
                    <span wire:loading.remove wire:target="loadModels">{{ __('agricart.ai.form.load_models') }}</span>
                    <span wire:loading wire:target="loadModels">{{ __('agricart.ai.form.loading_models') }}</span>
                </x-filament::button>

                @if ($selectedModelId)
                    <span class="agricart-ai-form__selected-model">
                        {!! __('agricart.ai.form.selected', ['name' => '<strong>'.$selectedModelName.'</strong>']) !!}
                    </span>
                @endif
            </div>

            @error('selectedModelId')
                <p class="agricart-ai-form__field-error">{{ $message }}</p>
            @enderror

            @if ($modelsLoaded)
                <div class="agricart-ai-form__model-list" wire:loading.class="is-loading" wire:target="loadModels">
                    @forelse ($this->filteredModels as $model)
                        <button
                            type="button"
                            class="agricart-ai-form__model-item"
                            wire:click="selectModel({{ json_encode($model['id']) }})"
                            @class(['is-selected' => $selectedModelId === $model['id']])
                        >
                            <span class="agricart-ai-form__model-name">{{ $model['name'] }}</span>
                            <span class="agricart-ai-form__model-id">{{ $model['id'] }}</span>
                            @if ($model['description'])
                                <span class="agricart-ai-form__model-desc">{{ $model['description'] }}</span>
                            @endif
                        </button>
                    @empty
                        <p class="agricart-ai-form__model-empty">{{ __('agricart.ai.form.no_models_match') }}</p>
                    @endforelse
                </div>
            @endif
        </div>
    </div>

    @if ($statusMessage)
        <div @class([
            'agricart-ai-form__alert',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
            'is-warning' => $statusType === 'warning',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <div class="agricart-ai-form__footer">
        <x-filament::button
            color="gray"
            outlined
            tag="button"
            type="button"
            wire:click="testConnection"
            wire:loading.attr="disabled"
            wire:target="testConnection,saveAndClose"
            :icon="Heroicon::OutlinedSignal"
        >
            <span wire:loading.remove wire:target="testConnection">{{ __('agricart.ai.form.test_connection') }}</span>
            <span wire:loading wire:target="testConnection">{{ __('agricart.ai.form.testing') }}</span>
        </x-filament::button>

        <div class="agricart-ai-form__footer-actions">
            <x-filament::button
                color="gray"
                outlined
                tag="button"
                type="button"
                x-on:click="$dispatch('close-form-panel')"
                :icon="Heroicon::OutlinedXMark"
            >
                {{ __('agricart.ai.form.cancel') }}
            </x-filament::button>

            <x-filament::button
                color="primary"
                tag="button"
                type="button"
                wire:click="saveAndClose"
                wire:loading.attr="disabled"
                wire:target="saveAndClose,testConnection"
                :icon="Heroicon::OutlinedCheck"
            >
                <span wire:loading.remove wire:target="saveAndClose">{{ __('agricart.ai.form.save_close') }}</span>
                <span wire:loading wire:target="saveAndClose">{{ __('agricart.ai.form.saving') }}</span>
            </x-filament::button>
        </div>
    </div>
</div>
