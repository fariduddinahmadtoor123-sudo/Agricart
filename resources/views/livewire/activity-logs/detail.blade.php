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

            @if (filled($log->user_agent))
                <section class="agricart-activity-log-detail__section">
                    <h3 class="agricart-activity-log-detail__section-title">{{ __('agricart.activity.detail.user_agent') }}</h3>
                    <pre class="agricart-activity-log-detail__block">{{ $log->user_agent }}</pre>
                </section>
            @endif

            <section class="agricart-activity-log-detail__section">
                <h3 class="agricart-activity-log-detail__section-title">{{ __('agricart.activity.detail.description') }}</h3>
                <p class="agricart-activity-log-detail__text">{{ $log->description }}</p>
            </section>

            @if (filled($log->properties))
                <section class="agricart-activity-log-detail__section">
                    <h3 class="agricart-activity-log-detail__section-title">{{ __('agricart.activity.detail.properties') }}</h3>
                    <pre class="agricart-activity-log-detail__block">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </section>
            @endif
        </div>
    @else
        <p class="agricart-activity-log-detail__empty">{{ __('agricart.activity.detail.not_found') }}</p>
    @endif
</div>
