<div class="agricart-categories agricart-store-inbox">
    <div class="agricart-store-messages__summary" aria-label="{{ __('agricart.inbox.admin.summary') }}">
        <button
            type="button"
            class="agricart-store-messages__summary-card is-pending {{ $statusFilter === 'open' && $queueFilter === 'all' ? 'is-active' : '' }}"
            wire:click="applyFilter('status:open')"
        >
            <span>{{ __('agricart.inbox.admin.summary_open') }}</span>
            <strong>{{ $openCount }}</strong>
        </button>
        <button
            type="button"
            class="agricart-store-messages__summary-card is-new {{ $queueFilter === 'unassigned' ? 'is-active' : '' }}"
            wire:click="applyFilter('queue:unassigned')"
        >
            <span>{{ __('agricart.inbox.admin.unassigned') }}</span>
            <strong>{{ $unassignedCount }}</strong>
        </button>
        <button
            type="button"
            class="agricart-store-messages__summary-card {{ $queueFilter === 'mine' ? 'is-active' : '' }}"
            wire:click="applyFilter('queue:mine')"
        >
            <span>{{ __('agricart.inbox.admin.mine') }}</span>
            <strong>{{ $mineCount }}</strong>
        </button>
        <button
            type="button"
            class="agricart-store-messages__summary-card is-new {{ $statusFilter === 'all' && $queueFilter === 'all' ? 'is-active' : '' }}"
            wire:click="applyFilter('status:all')"
        >
            <span>{{ __('agricart.inbox.admin.unread') }}</span>
            <strong>{{ $unreadCount }}</strong>
        </button>
    </div>

    <div class="agricart-store-inbox__topic-filters">
        <button type="button" class="{{ $topicFilter === 'all' ? 'is-active' : '' }}" wire:click="applyFilter('topic:all')">{{ __('agricart.inbox.admin.topic_all') }}</button>
        @foreach (\App\Models\AgricartConversation::TOPICS as $topic)
            <button type="button" class="{{ $topicFilter === $topic ? 'is-active' : '' }}" wire:click="applyFilter('topic:{{ $topic }}')">
                {{ __('agricart.inbox.topics.'.$topic) }}
            </button>
        @endforeach
    </div>

    @if ($conversations->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.inbox.admin.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.inbox.admin.col_customer') }}</th>
                        <th>{{ __('agricart.inbox.admin.col_subject') }}</th>
                        <th>{{ __('agricart.inbox.admin.col_topic') }}</th>
                        <th>{{ __('agricart.inbox.admin.col_status') }}</th>
                        <th>{{ __('agricart.inbox.admin.col_updated') }}</th>
                        <th>{{ __('agricart.inbox.admin.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($conversations as $conversation)
                        <tr wire:key="agricart-inbox-{{ $conversation->id }}" @class(['is-unread' => $conversation->unreadForStaff($viewer)])>
                            <td>{{ $conversation->displayName() }}</td>
                            <td>
                                <strong>{{ $conversation->subject }}</strong>
                                <p class="agricart-store-messages__preview">{{ trans_choice('agricart.inbox.admin.messages_count', $conversation->messages_count, ['count' => $conversation->messages_count]) }}</p>
                            </td>
                            <td>{{ __('agricart.inbox.topics.'.$conversation->topic) }}</td>
                            <td>
                                <span class="agricart-store-messages__status is-{{ $conversation->queueStateTone($viewer) }}">
                                    {{ __('agricart.inbox.queue.'.$conversation->queueState($viewer)) }}
                                </span>
                                <p class="agricart-store-messages__preview">
                                    @if ($conversation->assignedTo)
                                        {{ __('agricart.inbox.admin.assigned_to', ['name' => $conversation->assignedTo->name]) }}
                                    @else
                                        {{ __('agricart.inbox.admin.unassigned') }}
                                    @endif
                                </p>
                            </td>
                            <td>{{ agricart_datetime($conversation->last_message_at) }}</td>
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action is-primary" wire:click="openView({{ $conversation->id }})">
                                    {{ __('agricart.inbox.admin.view') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="agricart-categories__pagination">{{ $conversations->links() }}</div>
    @endif

    @if ($selectedConversation)
        <div class="agricart-store-messages__overlay" wire:click.self="closeView" x-data @keydown.escape.window="$wire.closeView()">
            <section class="agricart-store-messages__panel agricart-store-inbox__panel" role="dialog" aria-modal="true">
                <header class="agricart-store-messages__panel-head">
                    <div>
                        <span class="agricart-store-messages__panel-eyebrow">{{ __('agricart.inbox.heading') }}</span>
                        <h2>{{ $selectedConversation->subject }}</h2>
                    </div>
                    <button type="button" class="agricart-store-messages__panel-close" wire:click="closeView" aria-label="{{ __('agricart.inbox.admin.close') }}">×</button>
                </header>

                @php
                    $latestAdminReply = $selectedConversation->latestAdminMessage();
                    $customerReadLatest = $latestAdminReply instanceof \App\Models\AgricartMessage
                        && $selectedConversation->customerHasReadMessage($latestAdminReply);
                @endphp

                <dl class="agricart-store-messages__meta agricart-store-inbox__facts">
                    <div>
                        <dt>{{ __('agricart.inbox.admin.meta_customer') }}</dt>
                        <dd>{{ $selectedConversation->displayName() }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('agricart.inbox.admin.meta_status') }}</dt>
                        <dd>{{ __('agricart.inbox.queue.'.$selectedConversation->queueState($viewer)) }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('agricart.inbox.admin.meta_topic') }}</dt>
                        <dd>{{ __('agricart.inbox.topics.'.$selectedConversation->topic) }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('agricart.inbox.admin.meta_handler') }}</dt>
                        <dd>
                            @if ($selectedConversation->assignedTo)
                                {{ $selectedConversation->assignedTo->name }}
                            @else
                                {{ __('agricart.inbox.admin.unassigned') }}
                            @endif
                        </dd>
                    </div>
                </dl>

                @if ($latestAdminReply)
                    <p class="agricart-store-inbox__receipt-banner {{ $customerReadLatest ? 'is-read' : 'is-pending' }}" role="status">
                        {{ $customerReadLatest ? __('agricart.inbox.receipt.banner_read') : __('agricart.inbox.receipt.banner_unread') }}
                    </p>
                @endif

                <ol class="agricart-store-inbox__thread">
                    @foreach ($selectedConversation->messages as $message)
                        <li @class([
                            'agricart-store-inbox__bubble',
                            'is-customer' => $message->isFromCustomer(),
                            'is-admin' => $message->isFromAdmin(),
                            'is-system' => $message->isSystem(),
                        ])>
                            <p class="agricart-store-inbox__meta">
                                @if ($message->isSystem())
                                    {{ __('agricart.inbox.sender.system') }}
                                @elseif ($message->isFromAdmin())
                                    {{ __('agricart.inbox.admin.sender_staff', ['name' => $message->staffDisplayName()]) }}
                                    <span class="agricart-store-inbox__meta-hint">{{ __('agricart.inbox.admin.customer_sees_agricart') }}</span>
                                @else
                                    {{ $selectedConversation->displayName() }}
                                @endif
                                · {{ agricart_datetime($message->created_at) }}
                            </p>
                            <p class="agricart-store-inbox__bubble-body">{{ $message->body }}</p>
                            @if ($message->isFromAdmin())
                                @php $customerRead = $selectedConversation->customerHasReadMessage($message); @endphp
                                <p
                                    class="agricart-store-inbox__ticks {{ $customerRead ? 'is-read' : 'is-pending' }}"
                                    aria-label="{{ $customerRead ? __('agricart.inbox.receipt.read_by_customer') : __('agricart.inbox.receipt.not_read_by_customer') }}"
                                >
                                    <span class="agricart-store-inbox__ticks-marks" aria-hidden="true">{{ $customerRead ? '✓✓' : '✓' }}</span>
                                    <span>{{ $customerRead ? __('agricart.inbox.receipt.read_by_customer') : __('agricart.inbox.receipt.not_read_by_customer') }}</span>
                                </p>
                            @elseif ($message->isFromCustomer())
                                @php $teamRead = $selectedConversation->teamHasReadMessage($message); @endphp
                                <p
                                    class="agricart-store-inbox__ticks {{ $teamRead ? 'is-read' : 'is-pending' }}"
                                    aria-label="{{ $teamRead ? __('agricart.inbox.receipt.read_by_team') : __('agricart.inbox.receipt.delivered') }}"
                                >
                                    <span class="agricart-store-inbox__ticks-marks" aria-hidden="true">{{ $teamRead ? '✓✓' : '✓' }}</span>
                                    <span>{{ $teamRead ? __('agricart.inbox.receipt.read_by_team') : __('agricart.inbox.receipt.delivered') }}</span>
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ol>

                @if ($canEdit)
                    <div class="agricart-store-inbox__reply-editor">
                        <textarea rows="4" wire:model="replyText" class="fi-input" placeholder="{{ __('agricart.inbox.admin.reply_placeholder') }}"></textarea>
                        <div class="agricart-store-messages__panel-actions">
                            <button type="button" class="agricart-categories__action is-primary" wire:click="sendReply">
                                {{ __('agricart.inbox.admin.send_reply') }}
                            </button>
                            @if ((int) $selectedConversation->assigned_to_user_id !== (int) ($viewer?->id))
                                <button type="button" class="agricart-categories__action" wire:click="takeConversation({{ $selectedConversation->id }})">
                                    {{ __('agricart.inbox.admin.take') }}
                                </button>
                            @endif
                            @if ($selectedConversation->status === \App\Models\AgricartConversation::STATUS_OPEN)
                                <button type="button" class="agricart-categories__action" wire:click="closeConversation({{ $selectedConversation->id }})">
                                    {{ __('agricart.inbox.admin.close_thread') }}
                                </button>
                            @else
                                <button type="button" class="agricart-categories__action" wire:click="reopenConversation({{ $selectedConversation->id }})">
                                    {{ __('agricart.inbox.admin.reopen_thread') }}
                                </button>
                            @endif
                        </div>
                    </div>
                @endif

                @if (! empty($bridgedConversationId))
                    <p class="agricart-store-inbox__internal-note">{{ __('agricart.inbox.admin.bridged_to_platform', ['id' => $bridgedConversationId]) }}</p>
                @endif
            </section>
        </div>
    @endif
</div>
