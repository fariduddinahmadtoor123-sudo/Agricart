<div
    class="agricart-system-error-detail"
    x-data="{ copied: false }"
>
    @if ($error)
        <div class="agricart-system-error-detail__actions">
            <button
                type="button"
                class="agricart-system-error-detail__copy"
                x-on:click="navigator.clipboard.writeText(@js($report)).then(() => { copied = true; window.setTimeout(() => copied = false, 1800) })"
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

        <div class="agricart-system-error-detail__meta">
            <div><span>{{ __('agricart.system_errors.detail.reference') }}</span><code>{{ $error->reference }}</code></div>
            <div><span>{{ __('agricart.system_errors.columns.status') }}</span><strong>{{ __('agricart.system_errors.statuses.' . $error->status) }}</strong></div>
            <div><span>{{ __('agricart.system_errors.columns.severity') }}</span><strong>{{ __('agricart.system_errors.severities.' . $error->severity) }}</strong></div>
            <div><span>{{ __('agricart.system_errors.detail.exception') }}</span><strong>{{ $error->exception_class ?: '—' }}</strong></div>
            <div><span>{{ __('agricart.system_errors.detail.location') }}</span><strong>{{ $error->file ?: '—' }}{{ $error->line ? ':' . $error->line : '' }}</strong></div>
            <div><span>{{ __('agricart.system_errors.detail.request') }}</span><strong>{{ trim(($error->request_method ?: '') . ' ' . ($error->request_path ?: '—')) }}</strong></div>
            <div><span>{{ __('agricart.system_errors.detail.route') }}</span><strong>{{ $error->route_name ?: '—' }}</strong></div>
            <div><span>{{ __('agricart.system_errors.detail.actor') }}</span><strong>{{ $error->user?->name ?: '—' }}</strong></div>
            <div><span>{{ __('agricart.system_errors.columns.occurrences') }}</span><strong>{{ number_format($error->occurrences) }}</strong></div>
            <div><span>{{ __('agricart.system_errors.detail.first_seen') }}</span><strong>{{ $error->first_seen_at?->format('M j, Y g:i:s A') }}</strong></div>
            <div><span>{{ __('agricart.system_errors.columns.last_seen') }}</span><strong>{{ $error->last_seen_at?->format('M j, Y g:i:s A') }}</strong></div>
            <div><span>{{ __('agricart.system_errors.detail.environment') }}</span><strong>{{ $error->environment ?: '—' }}</strong></div>
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
    @elseif ($fileEntry)
        <div class="agricart-system-error-detail__actions">
            <button
                type="button"
                class="agricart-system-error-detail__copy"
                x-on:click="navigator.clipboard.writeText(@js($report)).then(() => { copied = true; window.setTimeout(() => copied = false, 1800) })"
            >
                <span x-show="! copied">{{ __('agricart.system_errors.actions.copy_report') }}</span>
                <span x-show="copied" x-cloak>{{ __('agricart.system_errors.actions.copied') }}</span>
            </button>
        </div>

        <div class="agricart-system-error-detail__meta">
            <div><span>{{ __('agricart.system_errors.columns.file') }}</span><strong>{{ $fileEntry['file'] }}</strong></div>
            <div><span>{{ __('agricart.system_errors.columns.severity') }}</span><strong>{{ __('agricart.system_errors.severities.' . $fileEntry['severity']) }}</strong></div>
            <div><span>{{ __('agricart.system_errors.columns.last_seen') }}</span><strong>{{ $fileEntry['timestamp'] ?? '—' }}</strong></div>
        </div>

        <section class="agricart-system-error-detail__section">
            <h3>{{ __('agricart.system_errors.detail.sanitized_file_entry') }}</h3>
            <pre>{{ $fileEntry['trace'] }}</pre>
        </section>
    @else
        <p class="agricart-system-error-detail__empty">{{ __('agricart.system_errors.detail.not_found') }}</p>
    @endif
</div>
