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
            </header>

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

            @if ($invoiceUrl && $packingUrl)
                <footer class="agricart-staff-inbox__footer">
                    <a
                        class="agricart-categories__action agricart-categories__action--primary"
                        href="{{ $invoiceUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >{{ __('staff_inbox.list.print_invoice') }}</a>
                    <a
                        class="agricart-categories__action agricart-categories__action--primary"
                        href="{{ $packingUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >{{ __('staff_inbox.list.print_packing') }}</a>
                </footer>
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
                                {{ $thread->last_message_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?? '—' }}
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
