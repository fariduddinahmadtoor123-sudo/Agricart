<div class="agricart-system-errors">
    <div class="agricart-system-errors__stats">
        <article>
            <span>{{ __('agricart.system_errors.stats.open') }}</span>
            <strong>{{ number_format($stats['open']) }}</strong>
        </article>
        <article class="is-critical">
            <span>{{ __('agricart.system_errors.stats.critical') }}</span>
            <strong>{{ number_format($stats['critical']) }}</strong>
        </article>
        <article>
            <span>{{ __('agricart.system_errors.stats.resolved') }}</span>
            <strong>{{ number_format($stats['resolved']) }}</strong>
        </article>
    </div>

    <div class="agricart-system-errors__source-tabs" role="tablist" aria-label="{{ __('agricart.system_errors.source_label') }}">
        <button
            type="button"
            role="tab"
            wire:click="showSource('inbox')"
            @class(['is-active' => $sourceFilter === 'inbox'])
            aria-selected="{{ $sourceFilter === 'inbox' ? 'true' : 'false' }}"
        >
            {{ __('agricart.system_errors.inbox') }}
        </button>
        <button
            type="button"
            role="tab"
            wire:click="showSource('file')"
            @class(['is-active' => $sourceFilter === 'file'])
            aria-selected="{{ $sourceFilter === 'file' ? 'true' : 'false' }}"
        >
            {{ __('agricart.system_errors.file_logs') }}
        </button>
    </div>

    @if ($sourceFilter === 'file')
        <p class="agricart-system-errors__file-notice">
            {{ __('agricart.system_errors.file_notice') }}
        </p>

        @if ($fileEntries === [])
            <div class="agricart-system-errors__empty">{{ __('agricart.system_errors.empty_file') }}</div>
        @else
            <div class="agricart-system-errors__table-wrap">
                <table class="agricart-system-errors__table">
                    <thead>
                        <tr>
                            <th>{{ __('agricart.system_errors.columns.severity') }}</th>
                            <th>{{ __('agricart.system_errors.columns.message') }}</th>
                            <th>{{ __('agricart.system_errors.columns.file') }}</th>
                            <th>{{ __('agricart.system_errors.columns.last_seen') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fileEntries as $entry)
                            <tr
                                wire:key="system-file-error-{{ $entry['key'] }}"
                                wire:click="openFileEntry('{{ $entry['key'] }}')"
                                class="agricart-system-errors__row"
                                role="button"
                                tabindex="0"
                                wire:keydown.enter="openFileEntry('{{ $entry['key'] }}')"
                                wire:keydown.space.prevent="openFileEntry('{{ $entry['key'] }}')"
                            >
                                <td>
                                    <span class="agricart-system-errors__severity is-{{ $entry['severity'] }}">
                                        {{ __('agricart.system_errors.severities.' . $entry['severity']) }}
                                    </span>
                                </td>
                                <td class="agricart-system-errors__message">{{ $entry['message'] }}</td>
                                <td>{{ $entry['file'] }}</td>
                                <td>{{ $entry['timestamp'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @elseif ($errors->isEmpty())
        <div class="agricart-system-errors__empty">{{ __('agricart.system_errors.empty') }}</div>
    @else
        <div class="agricart-system-errors__table-wrap">
            <table class="agricart-system-errors__table">
                <thead>
                    <tr>
                        @if (in_array('reference', $visibleColumns, true))
                            <th>{{ __('agricart.system_errors.columns.reference') }}</th>
                        @endif
                        @if (in_array('severity', $visibleColumns, true))
                            <th>{{ __('agricart.system_errors.columns.severity') }}</th>
                        @endif
                        @if (in_array('message', $visibleColumns, true))
                            <th>{{ __('agricart.system_errors.columns.message') }}</th>
                        @endif
                        @if (in_array('occurrences', $visibleColumns, true))
                            <th>{{ __('agricart.system_errors.columns.occurrences') }}</th>
                        @endif
                        @if (in_array('last_seen_at', $visibleColumns, true))
                            <th>{{ __('agricart.system_errors.columns.last_seen') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.system_errors.columns.status') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($errors as $error)
                        <tr
                            wire:key="system-error-{{ $error->id }}"
                            wire:click="openError({{ $error->id }})"
                            class="agricart-system-errors__row"
                            role="button"
                            tabindex="0"
                            wire:keydown.enter="openError({{ $error->id }})"
                            wire:keydown.space.prevent="openError({{ $error->id }})"
                        >
                            @if (in_array('reference', $visibleColumns, true))
                                <td><code>{{ $error->reference }}</code></td>
                            @endif
                            @if (in_array('severity', $visibleColumns, true))
                                <td>
                                    <span class="agricart-system-errors__severity is-{{ $error->severity }}">
                                        {{ __('agricart.system_errors.severities.' . $error->severity) }}
                                    </span>
                                </td>
                            @endif
                            @if (in_array('message', $visibleColumns, true))
                                <td class="agricart-system-errors__message">
                                    <strong>{{ class_basename($error->exception_class ?: __('agricart.system_errors.unknown')) }}</strong>
                                    <span>{{ $error->message }}</span>
                                </td>
                            @endif
                            @if (in_array('occurrences', $visibleColumns, true))
                                <td>{{ number_format($error->occurrences) }}</td>
                            @endif
                            @if (in_array('last_seen_at', $visibleColumns, true))
                                <td>{{ $error->last_seen_at?->format('M j, Y g:i A') }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>
                                    <span class="agricart-system-errors__status is-{{ $error->status }}">
                                        {{ __('agricart.system_errors.statuses.' . $error->status) }}
                                    </span>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-system-errors__pagination">
            {{ $errors->links() }}
        </div>
    @endif
</div>
