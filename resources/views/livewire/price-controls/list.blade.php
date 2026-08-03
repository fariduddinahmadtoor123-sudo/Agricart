<div class="agricart-categories agricart-price-controls">
    @if (session('price_control_error'))
        <div class="agricart-user-form__alert is-danger">{{ session('price_control_error') }}</div>
    @endif

    @if ($tiers->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('price_controls.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('name', $visibleColumns, true))
                            <th>{{ __('price_controls.list.col_name') }}</th>
                        @endif
                        @if (in_array('markup_percent', $visibleColumns, true))
                            <th>{{ __('price_controls.list.col_markup') }}</th>
                        @endif
                        @if (in_array('product_field', $visibleColumns, true))
                            <th>{{ __('price_controls.list.col_maps_to') }}</th>
                        @endif
                        @if (in_array('example_price', $visibleColumns, true))
                            <th>{{ __('price_controls.list.col_example', ['base' => $engine->formatPrice($exampleBase)]) }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('price_controls.list.col_status') }}</th>
                        @endif
                        <th>{{ __('price_controls.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tiers as $tier)
                        <tr wire:key="price-channel-tier-{{ $tier->id }}" class="agricart-categories__row">
                            @if (in_array('name', $visibleColumns, true))
                                <td>
                                    <strong>{{ $engine->tierPriceLabel($tier->tier_key) }}</strong>
                                </td>
                            @endif
                            @if (in_array('markup_percent', $visibleColumns, true))
                                <td>
                                    {{ rtrim(rtrim(number_format((float) $tier->markup_percent, 3, '.', ''), '0'), '.') }}%
                                    @if ($tier->isManual())
                                        <div class="agricart-price-controls__muted">{{ __('price_controls.list.retail_markup_note') }}</div>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('product_field', $visibleColumns, true))
                                <td>{{ $engine->productFieldLabel($tier->product_field) }}</td>
                            @endif
                            @if (in_array('example_price', $visibleColumns, true))
                                <td>
                                    @if ($tier->isManual() && (float) $tier->markup_percent <= 0)
                                        <span class="agricart-price-controls__muted">{{ __('price_controls.list.manual_example') }}</span>
                                    @else
                                        <code>{{ $engine->computeTierPrice($exampleBase, $tier) }}</code>
                                        @if ($tier->isManual())
                                            <div class="agricart-price-controls__muted">{{ __('price_controls.list.retail_example_note') }}</div>
                                        @endif
                                    @endif
                                </td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ __('price_controls.statuses.' . $tier->status) }}</td>
                            @endif
                            <td>
                                <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $tier->id }})">
                                    {{ __('price_controls.actions.edit_short') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
