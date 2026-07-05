<div class="agricart-ai-logs">
    @if ($logs->isEmpty())
        <div class="agricart-ai-logs__empty">
            <p>{{ __('agricart.ai.logs.empty') }}</p>
        </div>
    @else
        <div class="agricart-ai-logs__table-wrap">
            <table class="agricart-ai-logs__table">
                <thead>
                    <tr>
                        @if (in_array('id', $visibleColumns, true))
                            <th>{{ __('agricart.ai.logs.col_id') }}</th>
                        @endif
                        @if (in_array('type', $visibleColumns, true))
                            <th>{{ __('agricart.ai.logs.col_type') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.ai.logs.col_status') }}</th>
                        @endif
                        @if (in_array('model', $visibleColumns, true))
                            <th>{{ __('agricart.ai.logs.col_model') }}</th>
                        @endif
                        @if (in_array('duration_ms', $visibleColumns, true))
                            <th>{{ __('agricart.ai.logs.col_duration') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('agricart.ai.logs.col_created') }}</th>
                        @endif
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
                            @if (in_array('id', $visibleColumns, true))
                                <td>#{{ $log->id }}</td>
                            @endif
                            @if (in_array('type', $visibleColumns, true))
                                <td>{{ $log->type->label() }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>
                                    <span @class([
                                        'agricart-ai-logs__status',
                                        'is-success' => $log->isSuccess(),
                                        'is-error' => ! $log->isSuccess(),
                                    ])>
                                        {{ $log->status->label() }}
                                    </span>
                                </td>
                            @endif
                            @if (in_array('model', $visibleColumns, true))
                                <td>{{ $log->modelLabel() }}</td>
                            @endif
                            @if (in_array('duration_ms', $visibleColumns, true))
                                <td>
                                    @if ($log->duration_ms !== null)
                                        {{ $log->duration_ms }} ms
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $log->created_at?->format('M j, Y g:i A') }}</td>
                            @endif
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
