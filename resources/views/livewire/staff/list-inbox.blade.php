<div class="agricart-categories agricart-staff-inbox">
    @if ($openThread)
        @php
            $invoiceUrl = $invoiceOrder
                ? app(\App\Services\Sales\OnlineOrderDispatchService::class)->documentUrl($invoiceOrder, 'invoice')
                : null;
            $packingUrl = $invoiceOrder
                ? route('admin.online-sale-orders.packing-slip', ['order' => $invoiceOrder, 'autoprint' => 0])
                : null;
        @endphp

        <div class="agricart-staff-inbox__thread">
            <header class="agricart-staff-inbox__thread-head">
                <button type="button" class="agricart-categories__action" wire:click="closeThread">
                    {{ __('staff_inbox.list.back') }}
                </button>
                <div>
                    <h2>{{ $openThread->subject }}</h2>
                    @if ($openThread->context_type === \App\Models\StaffMessageThread::CONTEXT_ONLINE_SALE_ORDER && $openThread->context_id)
                        <p>{{ __('staff_inbox.list.order_label', ['order' => $invoiceOrder?->order_number ?? $openThread->onlineSaleOrder?->order_number ?? ('#'.$openThread->context_id)]) }}</p>
                    @endif
                </div>
                @if ($invoiceUrl && $packingUrl)
                    <div class="agricart-staff-inbox__quick-actions">
                        <a
                            class="agricart-categories__action is-primary"
                            href="{{ $invoiceUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                        >{{ __('staff_inbox.list.print_invoice') }}</a>
                        <a
                            class="agricart-categories__action is-primary"
                            href="{{ $packingUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                        >{{ __('staff_inbox.list.print_packing') }}</a>
                    </div>
                @endif
            </header>

            <section class="agricart-staff-inbox__activity" aria-label="{{ __('staff_inbox.list.activity') }}">
                <h3>{{ __('staff_inbox.list.activity') }}</h3>
                <ol class="agricart-staff-inbox__messages">
                    @forelse ($threadMessages as $message)
                        @php
                            $isMine = $authUser && (int) $message->sender_id === (int) $authUser->id;
                            $isForward = ($message->meta['type'] ?? null) === 'order_forward';
                        @endphp
                        <li
                            wire:key="staff-msg-{{ $message->id }}"
                            @class([
                                'agricart-staff-inbox__message',
                                'is-mine' => $isMine,
                                'is-forward' => $isForward,
                            ])
                        >
                            <p class="agricart-staff-inbox__message-meta">
                                <strong>{{ $message->sender?->name ?: '—' }}</strong>
                                · {{ agricart_datetime($message->created_at, 'd M Y H:i') }}
                                @if ($isForward)
                                    · {{ __('staff_inbox.list.forward_tag') }}
                                @endif
                            </p>
                            <p class="agricart-staff-inbox__message-body">{{ $message->body }}</p>
                            @if ($isForward && filled($message->meta['document_url'] ?? null))
                                <p class="agricart-staff-inbox__message-actions">
                                    <a
                                        href="{{ $message->meta['document_url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="agricart-categories__action"
                                    >{{ __('staff_inbox.list.open_document') }}</a>
                                </p>
                            @endif
                        </li>
                    @empty
                        <li class="agricart-staff-inbox__message is-empty">{{ __('staff_inbox.list.no_messages') }}</li>
                    @endforelse
                </ol>

                <form class="agricart-staff-inbox__reply" wire:submit.prevent="sendReply">
                    <label class="agricart-staff-inbox__reply-label">
                        <span class="sr-only">{{ __('staff_inbox.list.reply_placeholder') }}</span>
                        <textarea
                            rows="3"
                            class="fi-input"
                            wire:model="replyText"
                            placeholder="{{ __('staff_inbox.list.reply_placeholder') }}"
                        ></textarea>
                    </label>
                    @if ($bridgedConversationId)
                        <label class="agricart-staff-inbox__files-input">
                            <span>{{ __('communication.attachments.optional') }}</span>
                            <input type="file" wire:model="replyFiles" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip" />
                        </label>
                        <div wire:loading wire:target="replyFiles" class="agricart-staff-inbox__loading">
                            {{ __('communication.internal_notes.uploading') }}
                        </div>
                        @if (count($replyFiles) > 0)
                            <ul class="agricart-staff-inbox__pending-files">
                                @foreach ($replyFiles as $file)
                                    <li>{{ $file->getClientOriginalName() }}</li>
                                @endforeach
                            </ul>
                        @endif
                    @endif
                    @error('replyText')
                        <p class="agricart-staff-inbox__error">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="agricart-categories__action is-primary">
                        {{ __('staff_inbox.list.send_reply') }}
                    </button>
                </form>

                @if (($bridgedAttachments ?? collect())->isNotEmpty())
                    <section class="agricart-staff-inbox__attachments" aria-label="{{ __('communication.attachments.label') }}">
                        <h3>{{ __('communication.attachments.label') }}</h3>
                        <ul>
                            @foreach ($bridgedAttachments as $attachment)
                                <li wire:key="staff-att-{{ $attachment->id }}">
                                    <a
                                        href="{{ route('admin.communication.attachments.show', $attachment) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >{{ $attachment->original_name }}</a>
                                    <span>{{ strtoupper($attachment->kind) }} · {{ number_format($attachment->size / 1024, 1) }} KB</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </section>

            @if ($invoiceOrder)
                <div class="agricart-staff-inbox__notes" wire:key="comms-order-{{ $invoiceOrder->id }}">
                    <details class="agricart-staff-inbox__more">
                        <summary>{{ __('communication.tabs.more') }}</summary>
                        <x-communication.record-panels
                            linkable-type="online_sale_order"
                            :linkable-id="$invoiceOrder->id"
                        />
                    </details>
                </div>
            @endif

            @if ($invoiceSale)
                @include('livewire.staff.partials.inbox-invoice', [
                    'sale' => $invoiceSale,
                    'order' => $invoiceOrder,
                ])
            @elseif ($invoiceOrder)
                @include('livewire.staff.partials.inbox-order-invoice', [
                    'order' => $invoiceOrder,
                ])
            @endif
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        <th>{{ __('staff_inbox.list.col_subject') }}</th>
                        <th>{{ __('staff_inbox.list.col_from') }}</th>
                        <th>{{ __('staff_inbox.list.col_preview') }}</th>
                        <th>{{ __('staff_inbox.list.col_received') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($threads as $thread)
                        @php
                            $unread = $authUser && $thread->isUnreadFor($authUser);
                            $preview = $thread->latestMessage;
                            $from = $preview?->sender?->name
                                ?? $thread->creator?->name
                                ?? '—';
                        @endphp
                        <tr
                            wire:key="staff-thread-{{ $thread->id }}"
                            class="agricart-categories__row {{ $unread ? 'is-unread' : '' }}"
                        >
                            <td>
                                @if ($unread)
                                    <span class="agricart-store-messages__new-dot" title="{{ __('staff_inbox.list.unread') }}"></span>
                                @endif
                                <strong>{{ $thread->subject }}</strong>
                            </td>
                            <td>{{ $from }}</td>
                            <td>
                                <span class="agricart-store-messages__preview">
                                    {{ \Illuminate\Support\Str::limit((string) ($preview?->body ?? ''), 80) }}
                                </span>
                            </td>
                            <td>
                                {{ agricart_datetime($thread->last_message_at, 'd M Y H:i') }}
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="agricart-categories__action"
                                    wire:click="openThread({{ $thread->id }})"
                                >{{ __('staff_inbox.list.open') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                {{ filled($search) || $filter === 'unread'
                                    ? __('staff_inbox.list.empty_filtered')
                                    : __('staff_inbox.list.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($threads->hasPages())
            <div class="agricart-categories__pagination">
                {{ $threads->links() }}
            </div>
        @endif
    @endif
</div>
