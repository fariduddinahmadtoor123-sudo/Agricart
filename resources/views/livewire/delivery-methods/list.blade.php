<div class="agricart-categories agricart-delivery-methods">
    @if ($methods->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.delivery_methods.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('sort_order', $visibleColumns, true))
                            <th>{{ __('agricart.delivery_methods.list.col_sort') }}</th>
                        @endif
                        @if (in_array('name_en', $visibleColumns, true))
                            <th>{{ __('agricart.delivery_methods.list.col_name_en') }}</th>
                        @endif
                        @if (in_array('name_ur', $visibleColumns, true))
                            <th>{{ __('agricart.delivery_methods.list.col_name_ur') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.delivery_methods.list.col_status') }}</th>
                        @endif
                        @if (in_array('shipping_charge_mode', $visibleColumns, true))
                            <th>{{ __('agricart.delivery_methods.list.col_shipping_mode') }}</th>
                        @endif
                        @if (in_array('cod_allowed', $visibleColumns, true))
                            <th>{{ __('agricart.delivery_methods.list.col_cod') }}</th>
                        @endif
                        <th>{{ __('agricart.delivery_methods.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($methods as $method)
                        <tr wire:key="delivery-method-{{ $method->id }}" class="agricart-categories__row">
                            @if (in_array('sort_order', $visibleColumns, true))
                                <td>{{ $method->sort_order }}</td>
                            @endif
                            @if (in_array('name_en', $visibleColumns, true))
                                <td>{{ $method->name_en }}</td>
                            @endif
                            @if (in_array('name_ur', $visibleColumns, true))
                                <td dir="rtl">{{ $method->name_ur }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ ucfirst($method->status) }}</td>
                            @endif
                            @if (in_array('shipping_charge_mode', $visibleColumns, true))
                                <td>{{ $method->rule?->shipping_charge_mode?->label() ?? '—' }}</td>
                            @endif
                            @if (in_array('cod_allowed', $visibleColumns, true))
                                <td>{{ ($method->rule?->cod_allowed ?? false) ? 'Yes' : 'No' }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                @if ($canView)
                                    <button type="button" class="agricart-categories__action" wire:click="openView({{ $method->id }})">{{ __('agricart.delivery_methods.actions.view_short') }}</button>
                                @endif
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $method->id }})">{{ __('agricart.delivery_methods.actions.edit_short') }}</button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="deleteMethod({{ $method->id }})" wire:confirm="{{ __('agricart.delivery_methods.actions.delete_confirm') }}">{{ __('agricart.delivery_methods.actions.delete_short') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $methods->links() }}
        </div>
    @endif
</div>
