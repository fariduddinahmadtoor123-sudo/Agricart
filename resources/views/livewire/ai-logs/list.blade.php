<div class="agricart-ai-logs">
    <div class="agricart-ai-logs__filters">
        <div class="agricart-ai-logs__filter-group">
            <span class="agricart-ai-logs__filter-label">{{ __('agricart.ai.logs.filter_status') }}</span>
            <div class="agricart-ai-logs__chips">
                <button
                    type="button"
                    class="agricart-ai-logs__chip"
                    wire:click="$set('statusFilter', '')"
                    @class(['is-active' => $statusFilter === ''])
                >
                    {{ __('agricart.ai.logs.all') }}
                </button>
                @foreach ($statuses as $status)
                    <button
                        type="button"
                        class="agricart-ai-logs__chip"
                        wire:click="$set('statusFilter', '{{ $status->value }}')"
                        @class(['is-active' => $statusFilter === $status->value, 'is-success' => $status->value === 'success', 'is-error' => $status->value === 'error'])
                    >
                        {{ $status->label() }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="agricart-ai-logs__filter-group">
            <span class="agricart-ai-logs__filter-label">{{ __('agricart.ai.logs.filter_type') }}</span>
            <div class="agricart-ai-logs__chips">
                <button
                    type="button"
                    class="agricart-ai-logs__chip"
                    wire:click="$set('typeFilter', '')"
                    @class(['is-active' => $typeFilter === ''])
                >
                    {{ __('agricart.ai.logs.all') }}
                </button>
                @foreach ($types as $type)
                    <button
                        type="button"
                        class="agricart-ai-logs__chip"
                        wire:click="$set('typeFilter', '{{ $type->value }}')"
                        @class(['is-active' => $typeFilter === $type->value])
                    >
                        {{ $type->label() }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    @if ($logs->isEmpty())
        <div class="agricart-ai-logs__empty">
            <p>{{ __('agricart.ai.logs.empty') }}</p>
        </div>
    @else
        <div class="agricart-ai-logs__table-wrap">
            <table class="agricart-ai-logs__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.ai.logs.col_id') }}</th>
                        <th>{{ __('agricart.ai.logs.col_type') }}</th>
                        <th>{{ __('agricart.ai.logs.col_status') }}</th>
                        <th>{{ __('agricart.ai.logs.col_model') }}</th>
                        <th>{{ __('agricart.ai.logs.col_duration') }}</th>
                        <th>{{ __('agricart.ai.logs.col_created') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr
                            wire:key="ai-log-{{ $log->id }}"
                            wire:click="openLog({{ $log->id }})"
                            tabindex="0"
                            role="button"
                            class="agricart-ai-logs__row"
                        >
                            <td>#{{ $log->id }}</td>
                            <td>{{ $log->type->label() }}</td>
                            <td>
                                <span @class([
                                    'agricart-ai-logs__status',
                                    'is-success' => $log->isSuccess(),
                                    'is-error' => ! $log->isSuccess(),
                                ])>
                                    {{ $log->status->label() }}
                                </span>
                            </td>
                            <td>{{ $log->modelLabel() }}</td>
                            <td>
                                @if ($log->duration_ms !== null)
                                    {{ $log->duration_ms }} ms
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $log->created_at?->format('M j, Y g:i A') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-ai-logs__pagination">
            {{ $logs->links() }}
        </div>
    @endif
</div>
