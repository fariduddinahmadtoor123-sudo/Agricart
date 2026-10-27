@php
    $selected = $this->selectedEvent();
    $feedActual = $this->feedActual($selected);
    $needsManual = $this->needsManualActual($selected);
    $isManual = $this->isManualActual($selected);
    $effective = $this->effectiveActual($selected);
@endphp

<div class="fx-radar__ai" aria-label="{{ __('agricart.pages.fx-learn.econ_ai_title') }}">
    <header class="fx-radar__ai-head">
        <div>
            <p class="fx-radar__ai-kicker">{{ __('agricart.pages.fx-learn.econ_ai_kicker') }}</p>
            <h2 class="fx-radar__ai-title">{{ __('agricart.pages.fx-learn.econ_ai_title') }}</h2>
        </div>
        @if ($selected)
            <span class="fx-radar__ai-chip" dir="ltr">{{ $selected['ccy'] ?? '' }} · {{ $this->impactStars((string) ($selected['impact'] ?? '')) }}</span>
        @endif
    </header>

    @if ($selected === null)
        <p class="fx-radar__ai-empty">{{ __('agricart.pages.fx-learn.econ_ai_empty') }}</p>
    @else
        <div class="fx-radar__ai-event">
            <p class="fx-radar__ai-event-title">{{ $selected['title'] ?? '' }}</p>
            <dl class="fx-radar__ai-metrics" dir="ltr">
                <div>
                    <dt>{{ __('agricart.pages.fx-learn.econ_previous') }}</dt>
                    <dd>{{ $selected['previous'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('agricart.pages.fx-learn.econ_forecast') }}</dt>
                    <dd>{{ $selected['forecast'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt>
                        {{ __('agricart.pages.fx-learn.econ_actual') }}
                        @if ($isManual)
                            <span class="fx-radar__ai-badge">{{ __('agricart.pages.fx-learn.econ_ai_manual_badge') }}</span>
                        @elseif ($feedActual !== '—')
                            <span class="fx-radar__ai-badge fx-radar__ai-badge--feed">{{ __('agricart.pages.fx-learn.econ_ai_feed_badge') }}</span>
                        @endif
                    </dt>
                    <dd>{{ $effective ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        @if ($needsManual)
            <label class="fx-radar__ai-manual" for="fx-radar-manual-actual">
                <span>{{ __('agricart.pages.fx-learn.econ_ai_manual_label') }}</span>
                <input
                    id="fx-radar-manual-actual"
                    type="text"
                    class="fx-radar__ai-input"
                    dir="ltr"
                    placeholder="{{ __('agricart.pages.fx-learn.econ_ai_manual_placeholder') }}"
                    wire:model.live.debounce.300ms="manualActual"
                    autocomplete="off"
                />
            </label>
            @if ($isManual)
                <p class="fx-radar__ai-manual-hint">
                    <span class="fx-radar__ai-badge">{{ __('agricart.pages.fx-learn.econ_ai_manual_badge') }}</span>
                    {{ __('agricart.pages.fx-learn.econ_ai_manual_hint') }}
                </p>
            @endif
        @endif

        <div class="fx-radar__ai-actions">
            <button
                type="button"
                class="fx-radar__btn fx-radar__btn--view"
                wire:click="analyzeNews"
                wire:loading.attr="disabled"
                wire:target="analyzeNews"
                @disabled($this->analyzing || $effective === null)
            >
                <span wire:loading.remove wire:target="analyzeNews">{{ __('agricart.pages.fx-learn.econ_ai_analyze') }}</span>
                <span wire:loading wire:target="analyzeNews">{{ __('agricart.pages.fx-learn.econ_ai_analyzing') }}</span>
            </button>
        </div>
    @endif

    @if (filled($this->aiMessage))
        <p @class(['fx-radar__ai-msg', 'is-'.$this->aiMessageTone])>{{ $this->aiMessage }}</p>
    @endif

    @if (filled($this->aiBias) && filled($this->aiVerdict))
        <div class="fx-radar__ai-result" dir="auto">
            <div class="fx-radar__ai-result-grid">
                <div>
                    <p class="fx-radar__ai-label">{{ __('agricart.pages.fx-learn.econ_ai_bias') }}</p>
                    <p @class(['fx-radar__ai-bias', 'is-'.$this->aiBias])>
                        {{ __('agricart.pages.fx-learn.econ_ai_bias_'.$this->aiBias) }}
                    </p>
                </div>
                <div>
                    <p class="fx-radar__ai-label">{{ __('agricart.pages.fx-learn.econ_ai_verdict') }}</p>
                    <p class="fx-radar__ai-verdict">
                        {{ __('agricart.pages.fx-learn.econ_ai_verdict_'.$this->aiVerdict) }}
                    </p>
                </div>
            </div>

            @if (filled($this->aiSummaryEn))
                <p class="fx-radar__ai-summary" dir="ltr">{{ $this->aiSummaryEn }}</p>
            @endif
            @if (filled($this->aiSummaryUr))
                <p class="fx-radar__ai-summary fx-radar__ai-summary--ur" dir="rtl">{{ $this->aiSummaryUr }}</p>
            @endif

            @if (filled($this->aiInvertedNote) || filled($this->aiDollarBias))
                <div class="fx-radar__ai-invert">
                    @if (filled($this->aiDollarBias))
                        <p class="fx-radar__ai-label">{{ __('agricart.pages.fx-learn.econ_ai_dollar_bias') }}:
                            <strong>{{ __('agricart.pages.fx-learn.econ_ai_bias_'.$this->aiDollarBias) }}</strong>
                        </p>
                    @endif
                    @if (filled($this->aiInvertedNote))
                        <p class="fx-radar__ai-summary" dir="ltr">{{ $this->aiInvertedNote }}</p>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <p class="fx-radar__ai-disclaimer">{{ __('agricart.pages.fx-learn.econ_ai_disclaimer') }}</p>
</div>
