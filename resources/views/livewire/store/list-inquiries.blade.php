<div class="agricart-categories agricart-store-messages">
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
                        <tr wire:key="store-inquiry-{{ $inquiry->id }}" class="agricart-categories__row">
                            @if (in_array('name', $visibleColumns, true))
                                <td>{{ $inquiry->name }}</td>
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
                                <td>
                                    <details class="agricart-store-messages__details">
                                        <summary>{{ \Illuminate\Support\Str::limit($inquiry->message, 90) }}</summary>
                                        <p>{{ $inquiry->message }}</p>
                                        @if ($inquiry->source_url)
                                            <a href="{{ $inquiry->source_url }}" target="_blank" rel="noopener noreferrer">{{ __('agricart.store.admin.messages.source') }}</a>
                                        @endif
                                    </details>
                                </td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ __('agricart.store.admin.messages.statuses.'.$inquiry->status) }}</td>
                            @endif
                            @if (in_array('received', $visibleColumns, true))
                                <td>{{ $inquiry->created_at?->format('Y-m-d H:i') }}</td>
                            @endif
                            @if (in_array('actions', $visibleColumns, true))
                                <td class="agricart-categories__actions">
                                    @if ($canEdit && $inquiry->status === \App\Models\StoreInquiry::STATUS_NEW)
                                        <button type="button" class="agricart-categories__action" wire:click="markRead({{ $inquiry->id }})">{{ __('agricart.store.admin.messages.mark_read') }}</button>
                                    @endif
                                    @if ($canEdit && $inquiry->status !== \App\Models\StoreInquiry::STATUS_CLOSED)
                                        <button type="button" class="agricart-categories__action" wire:click="close({{ $inquiry->id }})">{{ __('agricart.store.admin.messages.close') }}</button>
                                    @elseif ($canEdit)
                                        <button type="button" class="agricart-categories__action" wire:click="reopen({{ $inquiry->id }})">{{ __('agricart.store.admin.messages.reopen') }}</button>
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
</div>
