<div class="agricart-categories agricart-forwarded-orders">
    <div class="agricart-online-order__list-note" role="note">
        {{ __('forwarded_orders.list.note') }}
    </div>

    @if ($statusMessage !== '')
        <p class="agricart-order-forward__status" role="status">{{ $statusMessage }}</p>
    @endif

    @if ($groups === [])
        <div class="agricart-categories__empty">
            <p>{{ filled($search) ? __('forwarded_orders.list.no_results') : __('forwarded_orders.list.empty') }}</p>
            @unless (filled($search))
                <p class="agricart-categories__empty-hint">{{ __('forwarded_orders.list.empty_hint') }}</p>
            @endunless
        </div>
    @else
        <ul class="agricart-forwarded-orders__groups">
            @foreach ($groups as $group)
                <li wire:key="fwd-group-{{ $group['key'] }}" class="agricart-forwarded-orders__group">
                    <button
                        type="button"
                        class="agricart-forwarded-orders__group-head"
                        wire:click="toggleGroup(@js($group['key']))"
                        aria-expanded="{{ $expandedKey === $group['key'] ? 'true' : 'false' }}"
                    >
                        <div class="agricart-forwarded-orders__identity">
                            <strong>{{ $group['name'] }}</strong>
                            <span>
                                {{ $group['type'] === 'supplier'
                                    ? __('forwarded_orders.list.recipient_supplier')
                                    : __('forwarded_orders.list.recipient_staff') }}
                            </span>
                            @if (filled($group['phone']))
                                <span>{{ $group['phone'] }}</span>
                            @endif
                        </div>
                        <div class="agricart-forwarded-orders__counts">
                            <span>{{ __('forwarded_orders.list.orders_count', ['count' => $group['order_count']]) }}</span>
                            <span>{{ __('forwarded_orders.list.lines_count', ['count' => $group['line_count']]) }}</span>
                            <span aria-hidden="true">{{ $expandedKey === $group['key'] ? '−' : '+' }}</span>
                        </div>
                    </button>

                    @if ($expandedKey === $group['key'])
                        <ul class="agricart-forwarded-orders__orders">
                            @foreach ($group['orders'] as $row)
                                <li wire:key="fwd-asg-{{ $row['assignment_type'] }}-{{ $row['assignment_id'] }}" class="agricart-forwarded-orders__order">
                                    <div class="agricart-forwarded-orders__order-meta">
                                        <code>{{ $row['order_number'] }}</code>
                                        <span>{{ __('forwarded_orders.list.document_'.$row['document']) }}</span>
                                    </div>
                                    <ul class="agricart-forwarded-orders__lines">
                                        @foreach ($row['lines'] as $line)
                                            <li>{{ $line }}</li>
                                        @endforeach
                                    </ul>
                                    @if ($canEdit)
                                        <button
                                            type="button"
                                            class="agricart-categories__action is-primary"
                                            wire:click="markReleased('{{ $row['assignment_type'] }}', {{ $row['assignment_id'] }})"
                                            wire:confirm="{{ __('forwarded_orders.actions.release_confirm') }}"
                                        >{{ __('forwarded_orders.actions.mark_released') }}</button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
