<div class="agricart-categories agricart-quantity-controls">
    @if (session('quantity_control_error'))
        <div class="agricart-user-form__alert is-danger">{{ session('quantity_control_error') }}</div>
    @endif

    @if ($tiers->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('quantity_controls.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('name', $visibleColumns, true))
                            <th>{{ __('quantity_controls.list.col_name') }}</th>
                        @endif
                        @if (in_array('min_quantity', $visibleColumns, true))
                            <th>{{ __('quantity_controls.list.col_min_quantity') }}</th>
                        @endif
                        @if (in_array('price_field', $visibleColumns, true))
                            <th>{{ __('quantity_controls.list.col_price_type') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('quantity_controls.list.col_status') }}</th>
                        @endif
                        <th>{{ __('quantity_controls.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tiers as $tier)
                        <tr wire:key="quantity-channel-tier-{{ $tier->id }}" class="agricart-categories__row">
                            @if (in_array('name', $visibleColumns, true))
                                <td>
                                    <strong>{{ $engine->tierQuantityLabel($tier->tier_key) }}</strong>
                                </td>
                            @endif
                            @if (in_array('min_quantity', $visibleColumns, true))
                                <td>
                                    <code>≥ {{ $engine->formatQuantity($tier->min_quantity) }}</code>
                                </td>
                            @endif
                            @if (in_array('price_field', $visibleColumns, true))
                                <td>{{ $engine->tierTypeLabel($tier->tier_key) }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ __('quantity_controls.statuses.' . $tier->status) }}</td>
                            @endif
                            <td>
                                <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $tier->id }})">
                                    {{ __('quantity_controls.actions.edit_short') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
