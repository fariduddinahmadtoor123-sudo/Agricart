@php
    use Filament\Support\Icons\Heroicon;

    $footerActions = \App\Filament\Layout\ModuleFormPanel::footerActions();

    $iconMap = [
        'sparkles' => Heroicon::OutlinedSparkles,
        'arrow-right' => Heroicon::OutlinedArrowRight,
        'check' => Heroicon::OutlinedCheck,
        'x-mark' => Heroicon::OutlinedXMark,
    ];
@endphp

<div class="agricart-form-panel__footer-inner">
    <div class="agricart-form-panel__progress" x-show="formKey !== 'categories' && formKey !== 'brands' && formKey !== 'units' && formKey !== 'packing-units' && formKey !== 'attributes' && formKey !== 'controls' && formKey !== 'products' && formKey !== 'users' && formKey !== 'roles'" x-cloak>
        <div class="agricart-form-panel__progress-meta">
            <span
                class="agricart-form-panel__progress-label"
                x-text="@js(__('agricart.form_panel.step')).replace(':current', currentStep).replace(':total', totalSteps)"
            ></span>
            <span
                class="agricart-form-panel__progress-step-name"
                x-text="progressSteps[currentStep - 1] || ''"
            ></span>
            <span
                class="agricart-form-panel__progress-percent"
                x-text="progressPercent + '%'"
            ></span>
        </div>

        <div
            class="agricart-form-panel__progress-track"
            role="progressbar"
            x-bind:aria-valuenow="progressPercent"
            aria-valuemin="0"
            aria-valuemax="100"
        >
            <div
                class="agricart-form-panel__progress-fill"
                x-bind:style="'width: ' + progressPercent + '%'"
            ></div>
        </div>

        <div class="agricart-form-panel__progress-dots">
            <template x-for="(step, index) in progressSteps" :key="index">
                <button
                    type="button"
                    class="agricart-form-panel__progress-dot"
                    x-bind:class="{ 'is-active': currentStep === index + 1, 'is-complete': currentStep > index + 1 }"
                    x-on:click="goToStep(index + 1)"
                    x-bind:title="step"
                ></button>
            </template>
        </div>
    </div>

    <div class="agricart-form-panel__actions">
        @foreach ($footerActions as $action)
            @php
                $icon = filled($action['icon']) ? ($iconMap[$action['icon']] ?? null) : null;
                $actionKey = $action['key'];
            @endphp

            @if ($actionKey === 'cancel')
                <x-filament::button
                    :color="$action['color']"
                    :outlined="$action['outlined']"
                    :icon="$icon"
                    tag="button"
                    type="button"
                    class="agricart-form-panel__action-btn"
                    x-on:click="close()"
                >
                    {{ $action['label'] }}
                </x-filament::button>
            @elseif ($actionKey === 'ai')
                <x-filament::button
                    color="warning"
                    :icon="$iconMap['sparkles']"
                    tag="button"
                    type="button"
                    class="agricart-form-panel__action-btn agricart-form-panel__action-btn--ai"
                    x-show="!['users', 'roles'].includes(formKey)"
                    x-cloak
                    x-bind:class="{ 'is-loading': (formKey === 'categories' && categoryAiEnriching) || (formKey === 'brands' && brandAiEnriching) || (formKey === 'units' && unitAiEnriching) || (formKey === 'packing-units' && packingUnitAiEnriching) || (formKey === 'attributes' && attributeAiEnriching) || (formKey === 'controls' && controlAiEnriching) || (formKey === 'products' && productAiEnriching) }"
                    x-bind:disabled="(formKey === 'categories' && categoryAiEnriching) || (formKey === 'brands' && brandAiEnriching) || (formKey === 'units' && unitAiEnriching) || (formKey === 'packing-units' && packingUnitAiEnriching) || (formKey === 'attributes' && attributeAiEnriching) || (formKey === 'controls' && controlAiEnriching) || (formKey === 'products' && productAiEnriching)"
                    x-bind:aria-busy="(formKey === 'categories' && categoryAiEnriching) || (formKey === 'brands' && brandAiEnriching) || (formKey === 'units' && unitAiEnriching) || (formKey === 'packing-units' && packingUnitAiEnriching) || (formKey === 'attributes' && attributeAiEnriching) || (formKey === 'controls' && controlAiEnriching) || (formKey === 'products' && productAiEnriching) ? 'true' : 'false'"
                    x-on:click="handleAction('ai')"
                >
                    <span class="agricart-form-panel__action-ai-inner">
                        <span class="agricart-form-panel__action-spinner" aria-hidden="true"></span>
                        <span class="agricart-form-panel__action-ai-label">{{ $action['label'] }}</span>
                        <span class="agricart-form-panel__action-ai-label agricart-form-panel__action-ai-label--busy">{{ __('agricart.categories.ai.enriching_short') }}</span>
                    </span>
                </x-filament::button>
            @else
                <x-filament::button
                    :color="$action['color']"
                    :outlined="$action['outlined']"
                    :icon="$icon"
                    tag="button"
                    type="button"
                    class="agricart-form-panel__action-btn"
                    x-on:click="handleAction('{{ $actionKey }}')"
                >
                    {{ $action['label'] }}
                </x-filament::button>
            @endif
        @endforeach
    </div>
</div>
