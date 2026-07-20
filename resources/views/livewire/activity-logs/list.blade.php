<div class="agricart-activity-logs">
    @if ($logs->isEmpty())
        <div class="agricart-activity-logs__empty">
            <p>{{ __('agricart.activity.empty') }}</p>
        </div>
    @else
        <div class="agricart-activity-logs__table-wrap">
            <table class="agricart-activity-logs__table">
                <thead>
                    <tr>
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('agricart.activity.col_created') }}</th>
                        @endif
                        @if (in_array('event', $visibleColumns, true))
                            <th>{{ __('agricart.activity.col_event') }}</th>
                        @endif
                        @if (in_array('actor', $visibleColumns, true))
                            <th>{{ __('agricart.activity.col_actor') }}</th>
                        @endif
                        @if (in_array('description', $visibleColumns, true))
                            <th>{{ __('agricart.activity.col_description') }}</th>
                        @endif
                        @if (in_array('ip_address', $visibleColumns, true))
                            <th>{{ __('agricart.activity.col_ip') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr
                            wire:key="activity-log-{{ $log->id }}"
                            wire:click="openLog({{ $log->id }})"
                            tabindex="0"
                            role="button"
                            class="agricart-activity-logs__row"
                        >
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $log->created_at?->format('M j, Y g:i A') }}</td>
                            @endif
                            @if (in_array('event', $visibleColumns, true))
                                <td>
                                    <span @class([
                                        'agricart-activity-logs__event',
                                        'is-auth' => $log->event->isAuth(),
                                        'is-mutate' => ! $log->event->isAuth(),
                                        'is-failed' => $log->event === \App\Enums\ActivityEvent::FailedLogin,
                                    ])>
                                        {{ $log->event->label() }}
                                    </span>
                                </td>
                            @endif
                            @if (in_array('actor', $visibleColumns, true))
                                <td>{{ $log->actorLabel() }}</td>
                            @endif
                            @if (in_array('description', $visibleColumns, true))
                                <td>{{ $log->description }}</td>
                            @endif
                            @if (in_array('ip_address', $visibleColumns, true))
                                <td>{{ $log->ip_address ?: '—' }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-activity-logs__pagination">
            {{ $logs->links() }}
        </div>
    @endif
</div>
