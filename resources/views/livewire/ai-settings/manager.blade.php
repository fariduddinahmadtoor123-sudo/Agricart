@php
    use Filament\Support\Icons\Heroicon;
@endphp

<div class="agricart-ai-page">
    @if ($activeConfiguration)
        <section @class([
            'agricart-ai-page__card',
            'agricart-ai-page__card--connected' => $activeConfiguration->isConnected(),
            'agricart-ai-page__card--paused' => $activeConfiguration->isPaused(),
        ])>
            <div class="agricart-ai-page__card-header">
                <div @class([
                    'agricart-ai-page__status-badge',
                    'is-connected' => $activeConfiguration->isConnected(),
                    'is-paused' => $activeConfiguration->isPaused(),
                    'is-failed' => ! $activeConfiguration->isConnected() && ! $activeConfiguration->isPaused(),
                ])>
                    <x-filament::icon :icon="Heroicon::OutlinedSignal" class="agricart-ai-page__status-icon" />
                    @if ($activeConfiguration->isPaused())
                        {{ __('agricart.ai.manager.paused') }}
                    @elseif ($activeConfiguration->isConnected())
                        {{ __('agricart.ai.manager.connected') }}
                    @else
                        {{ __('agricart.topbar.ai_failed') }}
                    @endif
                </div>

                <div class="agricart-ai-page__card-actions">
                    <button
                        type="button"
                        class="agricart-ai-page__link-btn"
                        wire:click="retestConnection"
                        wire:loading.attr="disabled"
                        wire:target="retestConnection,pauseAi,resumeAi,deleteConfiguration"
                        @disabled($activeConfiguration->isPaused())
                    >
                        <span wire:loading.remove wire:target="retestConnection">{{ __('agricart.ai.manager.retest') }}</span>
                        <span wire:loading wire:target="retestConnection">{{ __('agricart.ai.manager.retesting') }}</span>
                    </button>

                    @if ($activeConfiguration->isPaused())
                        <button
                            type="button"
                            class="agricart-ai-page__link-btn"
                            wire:click="resumeAi"
                            wire:loading.attr="disabled"
                            wire:target="resumeAi,pauseAi,deleteConfiguration,retestConnection"
                        >
                            <span wire:loading.remove wire:target="resumeAi">{{ __('agricart.ai.manager.resume') }}</span>
                            <span wire:loading wire:target="resumeAi">{{ __('agricart.ai.manager.resuming') }}</span>
                        </button>
                    @else
                        <button
                            type="button"
                            class="agricart-ai-page__link-btn"
                            wire:click="pauseAi"
                            wire:loading.attr="disabled"
                            wire:target="pauseAi,resumeAi,deleteConfiguration,retestConnection"
                        >
                            <span wire:loading.remove wire:target="pauseAi">{{ __('agricart.ai.manager.pause') }}</span>
                            <span wire:loading wire:target="pauseAi">{{ __('agricart.ai.manager.pausing') }}</span>
                        </button>
                    @endif

                    <button
                        type="button"
                        class="agricart-ai-page__link-btn is-danger"
                        wire:click="deleteConfiguration"
                        wire:confirm="{{ __('agricart.ai.manager.delete_confirm') }}"
                        wire:loading.attr="disabled"
                        wire:target="deleteConfiguration,pauseAi,resumeAi,retestConnection"
                    >
                        <span wire:loading.remove wire:target="deleteConfiguration">{{ __('agricart.ai.manager.delete') }}</span>
                        <span wire:loading wire:target="deleteConfiguration">{{ __('agricart.ai.manager.deleting') }}</span>
                    </button>
                </div>
            </div>

            <div class="agricart-ai-page__connected-grid">
                <div class="agricart-ai-page__metric">
                    <span class="agricart-ai-page__metric-label">{{ __('agricart.ai.manager.provider') }}</span>
                    <strong class="agricart-ai-page__metric-value">{{ $activeConfiguration->provider->label() }}</strong>
                </div>

                <div class="agricart-ai-page__metric">
                    <span class="agricart-ai-page__metric-label">{{ __('agricart.ai.manager.model') }}</span>
                    <strong class="agricart-ai-page__metric-value">{{ $activeConfiguration->displayName() }}</strong>
                </div>

                <div class="agricart-ai-page__metric">
                    <span class="agricart-ai-page__metric-label">{{ __('agricart.ai.manager.model_id') }}</span>
                    <strong class="agricart-ai-page__metric-value agricart-ai-page__metric-value--mono">{{ $activeConfiguration->model_id }}</strong>
                </div>

                <div class="agricart-ai-page__metric">
                    <span class="agricart-ai-page__metric-label">{{ __('agricart.ai.manager.last_tested') }}</span>
                    <strong class="agricart-ai-page__metric-value">
                        {{ $activeConfiguration->last_tested_at?->format('M j, Y g:i A') ?? __('agricart.ai.manager.not_tested') }}
                    </strong>
                </div>
            </div>

            @if (filled($activeConfiguration->last_test_message))
                <p class="agricart-ai-page__note">
                    {{ $activeConfiguration->last_test_message }}
                </p>
            @endif
        </section>
    @else
        <section class="agricart-ai-page__card agricart-ai-page__card--empty">
            <div class="agricart-ai-page__empty-icon-wrap">
                <x-filament::icon :icon="Heroicon::OutlinedSparkles" class="agricart-ai-page__empty-icon" />
            </div>
            <h3 class="agricart-ai-page__empty-title">{{ __('agricart.ai.manager.not_connected') }}</h3>
            <p class="agricart-ai-page__empty-text">
                {!! __('agricart.ai.manager.empty_help', [
                    'add' => '<strong>'.__('agricart.ai.manager.add_ai').'</strong>',
                ]) !!}
            </p>
        </section>
    @endif

    <section class="agricart-ai-page__card">
        <div class="agricart-ai-page__card-header">
            <div>
                <h3 class="agricart-ai-page__section-title">{{ __('agricart.ai.manager.prompt_title') }}</h3>
                <p class="agricart-ai-page__section-subtitle">
                    {{ __('agricart.ai.manager.prompt_subtitle') }}
                </p>
            </div>
        </div>

        <div class="agricart-ai-page__prompt-grid">
            <div class="agricart-ai-page__field agricart-ai-page__field--wide">
                <label class="agricart-ai-page__label" for="ai-test-prompt">{{ __('agricart.ai.manager.prompt_label') }}</label>
                <textarea
                    id="ai-test-prompt"
                    wire:model="testPrompt"
                    rows="3"
                    class="agricart-ai-page__textarea"
                    placeholder="{{ __('agricart.ai.manager.prompt_placeholder') }}"
                    @disabled($activeConfiguration === null || $activeConfiguration?->isPaused())
                ></textarea>
                @error('testPrompt')
                    <p class="agricart-ai-page__field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="agricart-ai-page__prompt-actions">
                <x-filament::button
                    color="primary"
                    :icon="Heroicon::OutlinedPaperAirplane"
                    tag="button"
                    type="button"
                    wire:click="sendTestPrompt"
                    wire:loading.attr="disabled"
                    wire:target="sendTestPrompt"
                    :disabled="$activeConfiguration === null || $activeConfiguration?->isPaused()"
                >
                    <span wire:loading.remove wire:target="sendTestPrompt">{{ __('agricart.ai.manager.send_prompt') }}</span>
                    <span wire:loading wire:target="sendTestPrompt">{{ __('agricart.ai.manager.sending') }}</span>
                </x-filament::button>
            </div>
        </div>

        @if ($testMessage)
            <div @class([
                'agricart-ai-page__alert',
                'is-success' => $testStatus === 'success',
                'is-danger' => $testStatus === 'danger',
            ])>
                {{ $testMessage }}
            </div>
        @endif

        @if ($testResponse)
            <div class="agricart-ai-page__response">
                <span class="agricart-ai-page__response-label">{{ __('agricart.ai.manager.response_label') }}</span>
                <p class="agricart-ai-page__response-text">{{ $testResponse }}</p>
            </div>
        @endif
    </section>
</div>
