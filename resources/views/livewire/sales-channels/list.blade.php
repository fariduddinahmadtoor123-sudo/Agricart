<div class="agricart-categories agricart-sales-channels">
    @if ($statusMessage)
        <div @class([
            'agricart-store-admin__status',
            'is-success' => $statusType === 'success',
            'is-error' => $statusType === 'error',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    @if ($records->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('sales_channels.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table agricart-sales-channels__table">
                <thead>
                    <tr>
                        @if (in_array('name', $visibleColumns, true))
                            <th>{{ __('sales_channels.list.col_name') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('sales_channels.list.col_status') }}</th>
                        @endif
                        @if (in_array('maintenance', $visibleColumns, true))
                            <th>{{ __('sales_channels.list.col_maintenance') }}</th>
                        @endif
                        <th>{{ __('sales_channels.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($records as $channel)
                        <tr wire:key="sales-channel-{{ $channel->id }}" class="agricart-categories__row">
                            @if (in_array('name', $visibleColumns, true))
                                <td class="agricart-sales-channels__name">
                                    <span class="agricart-sales-channels__name-label">{{ $channel->localizedName() }}</span>
                                    @if ($channel->slug === \App\Models\SalesChannel::SLUG_ONLINE_STORE)
                                        <a href="{{ $storefrontUrl }}" target="_blank" rel="noopener noreferrer" class="agricart-sales-channels__store-link">
                                            {{ __('sales_channels.actions.open_store') }}
                                        </a>
                                    @elseif ($channel->slug === \App\Models\SalesChannel::SLUG_FACEBOOK && filled($channel->setting('page_url')))
                                        <a href="{{ $channel->setting('page_url') }}" target="_blank" rel="noopener noreferrer" class="agricart-sales-channels__store-link">
                                            {{ __('sales_channels.facebook.open_page') }}
                                        </a>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td class="agricart-sales-channels__toggle-cell">
                                    @include('livewire.sales-channels._status-switch', [
                                        'channel' => $channel,
                                        'canToggle' => $canToggle,
                                    ])
                                </td>
                            @endif
                            @if (in_array('maintenance', $visibleColumns, true))
                                <td class="agricart-sales-channels__toggle-cell">
                                    @if ($channel->slug === \App\Models\SalesChannel::SLUG_ONLINE_STORE)
                                        @include('livewire.sales-channels._maintenance-switch', [
                                            'channel' => $channel,
                                            'canToggle' => $canToggle,
                                        ])
                                    @else
                                        <span class="agricart-sales-channels__na">—</span>
                                    @endif
                                </td>
                            @endif
                            <td>
                                <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $channel->id }})">
                                    {{ $channel->slug === \App\Models\SalesChannel::SLUG_FACEBOOK
                                        ? __('sales_channels.facebook.setup_short')
                                        : __('sales_channels.actions.edit_messages_short') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
