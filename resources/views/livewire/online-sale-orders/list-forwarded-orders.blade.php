<div class="agricart-categories agricart-forwarded-orders">
    @if (session('forwarded_orders_status'))
        <div class="agricart-forwarded-orders__status" role="status">
            {{ session('forwarded_orders_status') }}
        </div>
    @endif

    @if ($activeFilters !== [])
        <div class="agricart-list-filters">
            @foreach ($activeFilters as $filter)
                <button
                    type="button"
                    class="agricart-list-filters__chip"
                    wire:click="removeFilter('{{ $filter['field'] }}')"
                    wire:key="forwarded-filter-{{ $filter['field'] }}"
                >
                    <span>{{ $filter['label'] }}</span>
                    <span aria-hidden="true">×</span>
                </button>
            @endforeach

            @if ($hasListConstraints)
                <button type="button" class="agricart-list-filters__clear" wire:click="clearFilters">
                    {{ __('forwarded_orders.list.clear_filters') }}
                </button>
            @endif
        </div>
    @endif

    @if ($rows->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ $hasListConstraints ? __('forwarded_orders.list.no_results') : __('forwarded_orders.list.empty') }}</p>
            @unless ($hasListConstraints)
                <p class="agricart-categories__empty-hint">{{ __('forwarded_orders.list.empty_hint') }}</p>
            @endunless
        </div>
    @else
        <p class="agricart-forwarded-orders__hint">{{ __('forwarded_orders.list.release_hint') }}</p>

        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        <th>{{ __('forwarded_orders.list.col_order') }}</th>
                        <th>{{ __('forwarded_orders.list.col_invoice') }}</th>
                        <th>{{ __('forwarded_orders.list.col_from') }}</th>
                        <th>{{ __('forwarded_orders.list.col_to') }}</th>
                        <th>{{ __('forwarded_orders.list.col_status') }}</th>
                        <th>{{ __('forwarded_orders.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @php
                            $packingUrl = $row->order
                                ? route('admin.online-sale-orders.packing-slip', ['order' => $row->order, 'autoprint' => 0])
                                : null;
                            $isReleased = $row->released_at !== null;
                        @endphp
                        <tr wire:key="forwarded-row-{{ $row->id }}">
                            <td>
                                <code>{{ $row->order?->order_number ?? '—' }}</code>
                            </td>
                            <td>
                                @if (filled($row->order?->sale?->sale_number))
                                    <code>{{ $row->order->sale->sale_number }}</code>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $row->assignedBy?->name ?? '—' }}</td>
                            <td>{{ $row->user?->name ?? '—' }}</td>
                            <td>
                                <span @class(['agricart-forwarded-orders__badge', 'is-released' => $isReleased, 'is-open' => ! $isReleased])>
                                    {{ $isReleased
                                        ? __('forwarded_orders.list.status_released')
                                        : __('forwarded_orders.list.status_open') }}
                                </span>
                            </td>
                            <td class="agricart-categories__actions">
                                @if ($packingUrl)
                                    <a
                                        class="agricart-categories__action"
                                        href="{{ $packingUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >{{ __('forwarded_orders.actions.open_packing') }}</a>
                                @endif
                                @if ($canRelease && ! $isReleased)
                                    <button
                                        type="button"
                                        class="agricart-categories__action is-primary"
                                        wire:click="markReleased({{ $row->id }})"
                                        wire:confirm="{{ __('forwarded_orders.actions.release_confirm') }}"
                                    >{{ __('forwarded_orders.actions.mark_released') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $rows->links() }}
        </div>
    @endif
</div>
