<div class="agricart-categories agricart-store-messages">
    <div class="agricart-store-messages__summary" aria-label="{{ __('agricart.store.admin.messages.workflow_summary') }}">
        @foreach (\App\Models\StoreInquiry::STATUSES as $summaryStatus)
            <button
                type="button"
                class="agricart-store-messages__summary-card is-{{ $summaryStatus }} {{ $statusFilter === $summaryStatus ? 'is-active' : '' }}"
                wire:click="applyFilter('status:{{ $summaryStatus }}')"
            >
                <span>{{ __('agricart.store.admin.messages.statuses.'.$summaryStatus) }}</span>
                <strong>{{ $statusCounts[$summaryStatus] ?? 0 }}</strong>
            </button>
        @endforeach
    </div>

    @if ($inquiries->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.store.admin.messages.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('name', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.messages.col_name') }}</th>
                        @endif
                        @if (in_array('contact', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.messages.col_contact') }}</th>
                        @endif
                        @if (in_array('message', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.messages.col_message') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.messages.col_status') }}</th>
                        @endif
                        @if (in_array('received', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.messages.col_received') }}</th>
                        @endif
                        @if (in_array('actions', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.messages.col_actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inquiries as $inquiry)
                        <tr wire:key="store-inquiry-{{ $inquiry->id }}" class="agricart-categories__row {{ $inquiry->status === \App\Models\StoreInquiry::STATUS_NEW ? 'is-unread' : '' }}">
                            @if (in_array('name', $visibleColumns, true))
                                <td>
                                    <strong>{{ $inquiry->name }}</strong>
                                    @if ($inquiry->status === \App\Models\StoreInquiry::STATUS_NEW)
                                        <span class="agricart-store-messages__new-dot" title="{{ __('agricart.store.admin.messages.statuses.new') }}"></span>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('contact', $visibleColumns, true))
                                <td>
                                    <a href="tel:{{ $inquiry->phone }}">{{ $inquiry->phone }}</a>
                                    @if ($inquiry->email)
                                        <br><a href="mailto:{{ $inquiry->email }}">{{ $inquiry->email }}</a>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('message', $visibleColumns, true))
                                <td class="agricart-store-messages__preview">{{ \Illuminate\Support\Str::limit($inquiry->message, 110) }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>
                                    <span class="agricart-store-messages__status is-{{ $inquiry->status }}">
                                        {{ __('agricart.store.admin.messages.statuses.'.$inquiry->status) }}
                                    </span>
                                </td>
                            @endif
                            @if (in_array('received', $visibleColumns, true))
                                <td>{{ $inquiry->created_at?->format('Y-m-d H:i') }}</td>
                            @endif
                            @if (in_array('actions', $visibleColumns, true))
                                <td class="agricart-categories__actions agricart-store-messages__row-actions">
                                    <button type="button" class="agricart-categories__action is-primary" wire:click="openView({{ $inquiry->id }})">
                                        {{ __('agricart.store.admin.messages.view') }}
                                    </button>
                                    @if ($canEdit && $inquiry->status === \App\Models\StoreInquiry::STATUS_NEW)
                                        <button type="button" class="agricart-categories__action" wire:click="markRead({{ $inquiry->id }})">
                                            {{ __('agricart.store.admin.messages.mark_read') }}
                                        </button>
                                    @elseif ($canEdit && $inquiry->status === \App\Models\StoreInquiry::STATUS_CLOSED)
                                        <button type="button" class="agricart-categories__action" wire:click="reopen({{ $inquiry->id }})">
                                            {{ __('agricart.store.admin.messages.reopen') }}
                                        </button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $inquiries->links() }}
        </div>
    @endif

    @if ($selectedInquiry)
        <div class="agricart-store-messages__overlay" wire:click.self="closeView" x-data @keydown.escape.window="$wire.closeView()">
            <section
                class="agricart-store-messages__panel"
                role="dialog"
                aria-modal="true"
                aria-labelledby="store-message-detail-title"
            >
                <header class="agricart-store-messages__panel-head">
                    <div>
                        <span class="agricart-store-messages__panel-eyebrow">{{ __('agricart.store.admin.messages.detail_label') }}</span>
                        <h2 id="store-message-detail-title">{{ $selectedInquiry->name }}</h2>
                    </div>
                    <button type="button" class="agricart-store-messages__panel-close" wire:click="closeView" aria-label="{{ __('agricart.store.admin.messages.close_view') }}">×</button>
                </header>

                <div class="agricart-store-messages__panel-status">
                    <span class="agricart-store-messages__status is-{{ $selectedInquiry->status }}">
                        {{ __('agricart.store.admin.messages.statuses.'.$selectedInquiry->status) }}
                    </span>
                    <span>{{ __('agricart.store.admin.messages.received_at', ['date' => $selectedInquiry->created_at?->format('Y-m-d H:i')]) }}</span>
                </div>

                <dl class="agricart-store-messages__meta">
                    <div>
                        <dt>{{ __('agricart.store.admin.messages.col_contact') }}</dt>
                        <dd><a href="tel:{{ $selectedInquiry->phone }}">{{ $selectedInquiry->phone }}</a></dd>
                    </div>
                    <div>
                        <dt>{{ __('agricart.users.fields.email') }}</dt>
                        <dd>
                            @if ($selectedInquiry->email)
                                <a href="mailto:{{ $selectedInquiry->email }}">{{ $selectedInquiry->email }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>{{ __('agricart.store.admin.messages.read_at') }}</dt>
                        <dd>{{ $selectedInquiry->read_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('agricart.store.admin.messages.closed_at') }}</dt>
                        <dd>{{ $selectedInquiry->closed_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                    </div>
                </dl>

                <div class="agricart-store-messages__body">
                    <h3>{{ __('agricart.store.admin.messages.col_message') }}</h3>
                    <p>{{ $selectedInquiry->message }}</p>
                </div>

                @if ($selectedInquiry->source_url)
                    <a class="agricart-store-messages__source" href="{{ $selectedInquiry->source_url }}" target="_blank" rel="noopener noreferrer">
                        {{ __('agricart.store.admin.messages.source') }}
                    </a>
                @endif

                @if ($canEdit)
                    <footer class="agricart-store-messages__panel-actions">
                        @if ($selectedInquiry->status === \App\Models\StoreInquiry::STATUS_NEW)
                            <button type="button" class="agricart-categories__action" wire:click="markRead({{ $selectedInquiry->id }})">{{ __('agricart.store.admin.messages.mark_read') }}</button>
                        @elseif (in_array($selectedInquiry->status, [\App\Models\StoreInquiry::STATUS_READ, \App\Models\StoreInquiry::STATUS_PENDING], true))
                            <button type="button" class="agricart-categories__action" wire:click="markUnread({{ $selectedInquiry->id }})">{{ __('agricart.store.admin.messages.mark_unread') }}</button>
                        @endif

                        @if (! in_array($selectedInquiry->status, [\App\Models\StoreInquiry::STATUS_PENDING, \App\Models\StoreInquiry::STATUS_CLOSED], true))
                            <button type="button" class="agricart-categories__action is-warning" wire:click="markPending({{ $selectedInquiry->id }})">{{ __('agricart.store.admin.messages.mark_pending') }}</button>
                        @elseif ($selectedInquiry->status === \App\Models\StoreInquiry::STATUS_PENDING)
                            <button type="button" class="agricart-categories__action" wire:click="markRead({{ $selectedInquiry->id }})">{{ __('agricart.store.admin.messages.clear_pending') }}</button>
                        @endif

                        @if ($selectedInquiry->status !== \App\Models\StoreInquiry::STATUS_CLOSED)
                            <button type="button" class="agricart-categories__action is-success" wire:click="close({{ $selectedInquiry->id }})">{{ __('agricart.store.admin.messages.resolve_close') }}</button>
                        @else
                            <button type="button" class="agricart-categories__action is-warning" wire:click="reopen({{ $selectedInquiry->id }})">{{ __('agricart.store.admin.messages.reopen_pending') }}</button>
                        @endif
                    </footer>
                @endif
            </section>
        </div>
    @endif
</div>
