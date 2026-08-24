<div class="agricart-activity-logs">
    <div class="agricart-activity-logs__toolbar">
        <label class="agricart-activity-logs__user-label" for="activity-user-filter">
            {{ __('agricart.activity.summary.select_user') }}
        </label>
        <select
            id="activity-user-filter"
            class="agricart-activity-logs__user-select"
            wire:model.live="userFilter"
        >
            <option value="">{{ __('agricart.activity.summary.all_staff') }}</option>
            @foreach ($userOptions as $id => $label)
                <option value="{{ $id }}">{{ $label }}</option>
            @endforeach
        </select>
        <p class="agricart-activity-logs__toolbar-hint">
            {{ $selectedUser
                ? __('agricart.activity.summary.showing_user', ['name' => $selectedUser->name])
                : __('agricart.activity.summary.pick_user_hint') }}
        </p>
    </div>

    @if ($summary)
        <div class="agricart-activity-logs__summary" wire:key="activity-summary-{{ $selectedUser?->id }}">
            @foreach (['today', 'yesterday', 'last_7_days'] as $periodKey)
                @php $card = $summary[$periodKey]; @endphp
                <section class="agricart-activity-logs__summary-card">
                    <h3 class="agricart-activity-logs__summary-title">{{ $card['label'] }}</h3>
                    @if ($card['lines'] === [])
                        <p class="agricart-activity-logs__summary-empty">{{ __('agricart.activity.summary.empty_period') }}</p>
                    @else
                        <ul class="agricart-activity-logs__summary-list">
                            @foreach ($card['lines'] as $line)
                                <li>
                                    <span>{{ $line['label'] }}</span>
                                    <strong>{{ __('agricart.activity.summary.count_added', ['count' => $line['count']]) }}</strong>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach
        </div>
    @endif

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
