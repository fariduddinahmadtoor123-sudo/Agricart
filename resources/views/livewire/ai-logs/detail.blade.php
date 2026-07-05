<div class="agricart-ai-log-detail-wrap">
    @if ($log)
        <div class="agricart-ai-log-detail">
            <div class="agricart-ai-log-detail__meta">
                <div class="agricart-ai-log-detail__metric">
                    <span class="agricart-ai-log-detail__label">{{ __('agricart.ai.logs.detail.status') }}</span>
                    <span @class([
                        'agricart-ai-logs__status',
                        'is-success' => $log->isSuccess(),
                        'is-error' => ! $log->isSuccess(),
                    ])>
                        {{ $log->status->label() }}
                    </span>
                </div>

                <div class="agricart-ai-log-detail__metric">
                    <span class="agricart-ai-log-detail__label">{{ __('agricart.ai.logs.detail.type') }}</span>
                    <strong>{{ $log->type->label() }}</strong>
                </div>

                <div class="agricart-ai-log-detail__metric">
                    <span class="agricart-ai-log-detail__label">{{ __('agricart.ai.logs.detail.provider') }}</span>
                    <strong>{{ $log->providerLabel() }}</strong>
                </div>

                <div class="agricart-ai-log-detail__metric">
                    <span class="agricart-ai-log-detail__label">{{ __('agricart.ai.logs.detail.model') }}</span>
                    <strong>{{ $log->modelLabel() }}</strong>
                </div>

                <div class="agricart-ai-log-detail__metric">
                    <span class="agricart-ai-log-detail__label">{{ __('agricart.ai.logs.detail.duration') }}</span>
                    <strong>{{ $log->duration_ms !== null ? $log->duration_ms . ' ms' : '—' }}</strong>
                </div>

                <div class="agricart-ai-log-detail__metric">
                    <span class="agricart-ai-log-detail__label">{{ __('agricart.ai.logs.detail.http_status') }}</span>
                    <strong>{{ $log->http_status ?? '—' }}</strong>
                </div>

                <div class="agricart-ai-log-detail__metric">
                    <span class="agricart-ai-log-detail__label">{{ __('agricart.ai.logs.detail.source') }}</span>
                    <strong>{{ __('agricart.ai.logs.sources.' . $log->source) }}</strong>
                </div>

                <div class="agricart-ai-log-detail__metric">
                    <span class="agricart-ai-log-detail__label">{{ __('agricart.ai.logs.detail.created') }}</span>
                    <strong>{{ $log->created_at?->format('M j, Y g:i:s A') }}</strong>
                </div>
            </div>

            @if (filled($log->prompt))
                <section class="agricart-ai-log-detail__section">
                    <h3 class="agricart-ai-log-detail__section-title">{{ __('agricart.ai.logs.detail.prompt') }}</h3>
                    <pre class="agricart-ai-log-detail__block">{{ $log->prompt }}</pre>
                </section>
            @endif

            @if (filled($log->response))
                <section class="agricart-ai-log-detail__section">
                    <h3 class="agricart-ai-log-detail__section-title">{{ __('agricart.ai.logs.detail.response') }}</h3>
                    <pre class="agricart-ai-log-detail__block is-success">{{ $log->response }}</pre>
                </section>
            @endif

            @if (filled($log->error_message))
                <section class="agricart-ai-log-detail__section">
                    <h3 class="agricart-ai-log-detail__section-title">{{ __('agricart.ai.logs.detail.error') }}</h3>
                    <pre class="agricart-ai-log-detail__block is-error">{{ $log->error_message }}</pre>
                </section>
            @endif

            @if (filled($log->metadata))
                <section class="agricart-ai-log-detail__section">
                    <h3 class="agricart-ai-log-detail__section-title">{{ __('agricart.ai.logs.detail.metadata') }}</h3>
                    <pre class="agricart-ai-log-detail__block">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </section>
            @endif
        </div>
    @else
        <p class="agricart-ai-log-detail__empty">{{ __('agricart.ai.logs.detail.not_found') }}</p>
    @endif
</div>
