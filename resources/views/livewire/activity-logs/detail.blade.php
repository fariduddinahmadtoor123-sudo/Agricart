@php
    $changeRows = $log
        ? app(\App\Services\Activity\ActivityChangePresenter::class)->rows($log)
        : [];
@endphp

<div class="agricart-activity-log-detail-wrap">
    @if ($log)
        <div class="agricart-activity-log-detail">
            <div class="agricart-activity-log-detail__meta">
                <div class="agricart-activity-log-detail__metric">
                    <span class="agricart-activity-log-detail__label">{{ __('agricart.activity.detail.event') }}</span>
                    <strong>{{ $log->event->label() }}</strong>
                </div>

                <div class="agricart-activity-log-detail__metric">
                    <span class="agricart-activity-log-detail__label">{{ __('agricart.activity.detail.actor') }}</span>
                    <strong>{{ $log->actorLabel() }}</strong>
                </div>

                <div class="agricart-activity-log-detail__metric">
                    <span class="agricart-activity-log-detail__label">{{ __('agricart.activity.detail.subject') }}</span>
                    <strong>
                        @if ($log->subject_type)
                            {{ $log->subjectTypeLabel() }}
                            @if (filled($log->subject_label))
                                — {{ $log->subject_label }}
                            @endif
                        @else
                            {{ $log->subject_label ?: '—' }}
                        @endif
                    </strong>
                </div>

                <div class="agricart-activity-log-detail__metric">
                    <span class="agricart-activity-log-detail__label">{{ __('agricart.activity.detail.ip') }}</span>
                    <strong>{{ $log->ip_address ?: '—' }}</strong>
                </div>

                <div class="agricart-activity-log-detail__metric">
                    <span class="agricart-activity-log-detail__label">{{ __('agricart.activity.detail.created') }}</span>
                    <strong>{{ $log->created_at?->format('M j, Y g:i:s A') }}</strong>
                </div>
            </div>

            <section class="agricart-activity-log-detail__section">
                <h3 class="agricart-activity-log-detail__section-title">{{ __('agricart.activity.detail.description') }}</h3>
                <p class="agricart-activity-log-detail__text">{{ $log->description }}</p>
            </section>

            @if ($changeRows !== [])
                <section class="agricart-activity-log-detail__section">
                    <h3 class="agricart-activity-log-detail__section-title">{{ __('agricart.activity.detail.changes') }}</h3>
                    <div class="agricart-activity-log-detail__changes-wrap">
                        <table class="agricart-activity-log-detail__changes">
                            <thead>
                                <tr>
                                    <th>{{ __('agricart.activity.detail.field') }}</th>
                                    <th>{{ __('agricart.activity.detail.before') }}</th>
                                    <th>{{ __('agricart.activity.detail.after') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($changeRows as $row)
                                    <tr>
                                        <td>{{ $row['field'] }}</td>
                                        <td>{{ $row['before'] }}</td>
                                        <td>{{ $row['after'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            @if (filled($log->user_agent))
                <section class="agricart-activity-log-detail__section">
                    <h3 class="agricart-activity-log-detail__section-title">{{ __('agricart.activity.detail.device') }}</h3>
                    <p class="agricart-activity-log-detail__text agricart-activity-log-detail__text--muted">
                        {{ \Illuminate\Support\Str::limit($log->user_agent, 160) }}
                    </p>
                </section>
            @endif
        </div>
    @else
        <p class="agricart-activity-log-detail__empty">{{ __('agricart.activity.detail.not_found') }}</p>
    @endif
</div>
