<div class="agricart-categories agricart-store-inbox">
    <div class="agricart-store-messages__summary" aria-label="{{ __('agricart.inbox.admin.summary') }}">
        <button
            type="button"
            class="agricart-store-messages__summary-card is-pending {{ $statusFilter === 'open' ? 'is-active' : '' }}"
            wire:click="applyFilter('status:open')"
        >
            <span>{{ __('agricart.inbox.statuses.open') }}</span>
            <strong>{{ $openCount }}</strong>
        </button>
        <button
            type="button"
            class="agricart-store-messages__summary-card is-new {{ $statusFilter === 'all' ? 'is-active' : '' }}"
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
                        <tr wire:key="agricart-inbox-{{ $conversation->id }}" @class(['is-unread' => $conversation->unreadForAdmin()])>
                            <td>{{ $conversation->displayName() }}</td>
                            <td>
                                <strong>{{ $conversation->subject }}</strong>
                                <p class="agricart-store-messages__preview">{{ __('agricart.inbox.admin.messages_count', ['count' => $conversation->messages_count]) }}</p>
                            </td>
                            <td>{{ __('agricart.inbox.topics.'.$conversation->topic) }}</td>
                            <td>
                                <span class="agricart-store-messages__status is-{{ $conversation->status === 'open' ? 'pending' : 'closed' }}">
                                    {{ __('agricart.inbox.statuses.'.$conversation->status) }}
                                </span>
                            </td>
                            <td>{{ $conversation->last_message_at?->format('Y-m-d H:i') ?: '—' }}</td>
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

                <div class="agricart-store-messages__panel-status">
                    <span>{{ __('agricart.inbox.topics.'.$selectedConversation->topic) }}</span>
                    <span>{{ __('agricart.inbox.statuses.'.$selectedConversation->status) }}</span>
                    <span>{{ $selectedConversation->displayName() }}</span>
                    @if (! empty($bridgedConversationId))
                        <span>{{ __('agricart.inbox.admin.bridged_to_platform', ['id' => $bridgedConversationId]) }}</span>
                    @endif
                </div>

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
                                    {{ __('agricart.inbox.sender.agricart') }}
                                @else
                                    {{ $selectedConversation->displayName() }}
                                @endif
                                · {{ $message->created_at?->format('Y-m-d H:i') }}
                            </p>
                            <p>{{ $message->body }}</p>
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
            </section>
        </div>
    @endif
</div>
