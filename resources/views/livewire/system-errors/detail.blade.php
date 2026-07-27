<div
    class="agricart-system-error-detail"
    x-data="{
        copied: false,
        showTechnical: false,
        async copyReport() {
            const text = (this.$refs.reportText?.textContent || '').trim();
            if (! text) {
                return;
            }

            const markCopied = () => {
                this.copied = true;
                window.setTimeout(() => { this.copied = false }, 1800);
            };

            try {
                if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                    await navigator.clipboard.writeText(text);
                    markCopied();
                    return;
                }
            } catch (e) {
                // Fall through to execCommand fallback (common on http:// hosts).
            }

            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.top = '0';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();

            try {
                if (document.execCommand('copy')) {
                    markCopied();
                }
            } finally {
                document.body.removeChild(textarea);
            }
        }
    }"
>
    @if ($error)
        <div class="agricart-system-error-detail__actions">
            <button
                type="button"
                class="agricart-system-error-detail__copy"
                x-on:click="copyReport()"
            >
                <span x-show="! copied">{{ __('agricart.system_errors.actions.copy_report') }}</span>
                <span x-show="copied" x-cloak>{{ __('agricart.system_errors.actions.copied') }}</span>
            </button>

            @if ($canManage && $error->isOpen())
                <button type="button" wire:click="resolve" class="agricart-system-error-detail__resolve">
                    {{ __('agricart.system_errors.actions.resolve') }}
                </button>
            @elseif ($canManage)
                <button type="button" wire:click="reopen" class="agricart-system-error-detail__reopen">
                    {{ __('agricart.system_errors.actions.reopen') }}
                </button>
            @endif
        </div>

        <pre class="agricart-system-error-detail__report-source" hidden x-ref="reportText">{{ $report }}</pre>

        @if (is_array($guidance))
            <section class="agricart-system-error-detail__guidance is-{{ $guidance['worry'] }} is-{{ $guidance['bucket'] }}">
                <p class="agricart-system-error-detail__guidance-kicker">{{ __('agricart.system_errors.guidance.heading', [], 'ur') }}</p>
                <h3 dir="rtl" lang="ur">{{ $guidance['title'] }}</h3>
                <div class="agricart-system-error-detail__guidance-chips">
                    <span class="is-bucket" dir="rtl" lang="ur">{{ __('agricart.system_errors.guidance.source_label', [], 'ur') }}: {{ $guidance['bucket_label'] }}</span>
                    <span class="is-worry" dir="rtl" lang="ur">{{ __('agricart.system_errors.guidance.worry_label', [], 'ur') }}: {{ $guidance['worry_label'] }}</span>
                </div>
                <p dir="rtl" lang="ur">{{ $guidance['summary'] }}</p>
                <p class="agricart-system-error-detail__guidance-action" dir="rtl" lang="ur"><strong>{{ __('agricart.system_errors.guidance.action_label', [], 'ur') }}:</strong> {{ $guidance['action'] }}</p>
                <div class="agricart-system-error-detail__guidance-en" dir="ltr" lang="en">
                    <p class="agricart-system-error-detail__guidance-kicker">English</p>
                    <p><strong>{{ $guidance['title_en'] }}</strong></p>
                    <p>{{ $guidance['summary_en'] }}</p>
                    <p><strong>What to do:</strong> {{ $guidance['action_en'] }}</p>
                </div>
            </section>
        @endif

        <div class="agricart-system-error-detail__meta">
            <div><span>{{ __('agricart.system_errors.detail.reference') }}</span><code>{{ $error->reference }}</code></div>
            <div><span>{{ __('agricart.system_errors.columns.status') }}</span><strong>{{ __('agricart.system_errors.statuses.' . $error->status) }}</strong></div>
            <div><span>{{ __('agricart.system_errors.columns.severity') }}</span><strong>{{ __('agricart.system_errors.severities.' . $error->severity) }}</strong></div>
            <div><span>{{ __('agricart.system_errors.detail.request') }}</span><strong>{{ trim(($error->request_method ?: '') . ' ' . ($error->request_path ?: '—')) }}</strong></div>
            <div><span>{{ __('agricart.system_errors.columns.occurrences') }}</span><strong>{{ number_format($error->occurrences) }}</strong></div>
            <div><span>{{ __('agricart.system_errors.columns.last_seen') }}</span><strong>{{ $error->last_seen_at?->format('M j, Y g:i:s A') }}</strong></div>
            <div><span>{{ __('agricart.system_errors.detail.environment') }}</span><strong>{{ $error->environment ?: '—' }}</strong></div>
            <div><span>{{ __('agricart.system_errors.detail.actor') }}</span><strong>{{ $error->user?->name ?: '—' }}</strong></div>
            @if ($error->verified_at)
                <div><span>{{ __('agricart.system_errors.detail.verified_at') }}</span><strong>{{ $error->verified_at->format('M j, Y g:i:s A') }}</strong></div>
            @endif
        </div>

        @if (filled($error->resolution_note))
            <section class="agricart-system-error-detail__section">
                <h3>{{ __('agricart.system_errors.detail.resolution') }}</h3>
                <pre>{{ $error->resolution_note }}</pre>
            </section>
        @endif

        <section class="agricart-system-error-detail__section">
            <h3>{{ __('agricart.system_errors.columns.message') }}</h3>
            <pre class="is-error">{{ $error->message }}</pre>
        </section>

        <div class="agricart-system-error-detail__technical">
            <button
                type="button"
                class="agricart-system-error-detail__technical-toggle"
                x-on:click="showTechnical = ! showTechnical"
                x-text="showTechnical ? '{{ __('agricart.system_errors.detail.hide_technical') }}' : '{{ __('agricart.system_errors.detail.show_technical') }}'"
            ></button>

            <div x-show="showTechnical" x-cloak class="agricart-system-error-detail__technical-body">
                <div class="agricart-system-error-detail__meta">
                    <div><span>{{ __('agricart.system_errors.detail.exception') }}</span><strong>{{ $error->exception_class ?: '—' }}</strong></div>
                    <div><span>{{ __('agricart.system_errors.detail.location') }}</span><strong>{{ $error->file ?: '—' }}{{ $error->line ? ':' . $error->line : '' }}</strong></div>
                    <div><span>{{ __('agricart.system_errors.detail.route') }}</span><strong>{{ $error->route_name ?: '—' }}</strong></div>
                    <div><span>{{ __('agricart.system_errors.detail.first_seen') }}</span><strong>{{ $error->first_seen_at?->format('M j, Y g:i:s A') }}</strong></div>
                </div>

                @if (filled($error->context))
                    <section class="agricart-system-error-detail__section">
                        <h3>{{ __('agricart.system_errors.detail.context') }}</h3>
                        <pre>{{ json_encode($error->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </section>
                @endif

                @if (filled($error->trace))
                    <section class="agricart-system-error-detail__section">
                        <h3>{{ __('agricart.system_errors.detail.trace') }}</h3>
                        <pre>{{ $error->trace }}</pre>
                    </section>
                @endif
            </div>
        </div>
    @elseif ($fileEntry)
        <div class="agricart-system-error-detail__actions">
            <button
                type="button"
                class="agricart-system-error-detail__copy"
                x-on:click="copyReport()"
            >
                <span x-show="! copied">{{ __('agricart.system_errors.actions.copy_report') }}</span>
                <span x-show="copied" x-cloak>{{ __('agricart.system_errors.actions.copied') }}</span>
            </button>
        </div>

        <pre class="agricart-system-error-detail__report-source" hidden x-ref="reportText">{{ $report }}</pre>

        @if (is_array($guidance))
            <section class="agricart-system-error-detail__guidance is-{{ $guidance['worry'] }} is-{{ $guidance['bucket'] }}">
                <p class="agricart-system-error-detail__guidance-kicker">{{ __('agricart.system_errors.guidance.heading', [], 'ur') }}</p>
                <h3 dir="rtl" lang="ur">{{ $guidance['title'] }}</h3>
                <div class="agricart-system-error-detail__guidance-chips">
                    <span class="is-bucket" dir="rtl" lang="ur">{{ __('agricart.system_errors.guidance.source_label', [], 'ur') }}: {{ $guidance['bucket_label'] }}</span>
                    <span class="is-worry" dir="rtl" lang="ur">{{ __('agricart.system_errors.guidance.worry_label', [], 'ur') }}: {{ $guidance['worry_label'] }}</span>
                </div>
                <p dir="rtl" lang="ur">{{ $guidance['summary'] }}</p>
                <p class="agricart-system-error-detail__guidance-action" dir="rtl" lang="ur"><strong>{{ __('agricart.system_errors.guidance.action_label', [], 'ur') }}:</strong> {{ $guidance['action'] }}</p>
                <div class="agricart-system-error-detail__guidance-en" dir="ltr" lang="en">
                    <p class="agricart-system-error-detail__guidance-kicker">English</p>
                    <p><strong>{{ $guidance['title_en'] }}</strong></p>
                    <p>{{ $guidance['summary_en'] }}</p>
                    <p><strong>What to do:</strong> {{ $guidance['action_en'] }}</p>
                </div>
            </section>
        @endif

        <div class="agricart-system-error-detail__meta">
            <div><span>{{ __('agricart.system_errors.columns.file') }}</span><strong>{{ $fileEntry['file'] }}</strong></div>
            <div><span>{{ __('agricart.system_errors.columns.severity') }}</span><strong>{{ __('agricart.system_errors.severities.' . $fileEntry['severity']) }}</strong></div>
            <div><span>{{ __('agricart.system_errors.columns.last_seen') }}</span><strong>{{ $fileEntry['timestamp'] ?? '—' }}</strong></div>
        </div>

        <div class="agricart-system-error-detail__technical">
            <button
                type="button"
                class="agricart-system-error-detail__technical-toggle"
                x-on:click="showTechnical = ! showTechnical"
                x-text="showTechnical ? '{{ __('agricart.system_errors.detail.hide_technical') }}' : '{{ __('agricart.system_errors.detail.show_technical') }}'"
            ></button>
            <div x-show="showTechnical" x-cloak>
                <section class="agricart-system-error-detail__section">
                    <h3>{{ __('agricart.system_errors.detail.sanitized_file_entry') }}</h3>
                    <pre>{{ $fileEntry['trace'] }}</pre>
                </section>
            </div>
        </div>
    @else
        <p class="agricart-system-error-detail__empty">{{ __('agricart.system_errors.detail.not_found') }}</p>
    @endif
</div>
