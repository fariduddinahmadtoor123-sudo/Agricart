<div class="agricart-categories agricart-packing-units">
    @if ($packingUnits->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.packing_units.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('code', $visibleColumns, true))
                            <th>{{ __('agricart.packing_units.list.col_code') }}</th>
                        @endif
                        @if (in_array('name_en', $visibleColumns, true))
                            <th>{{ __('agricart.packing_units.list.col_name_en') }}</th>
                        @endif
                        @if (in_array('name_ur', $visibleColumns, true))
                            <th>{{ __('agricart.packing_units.list.col_name_ur') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.packing_units.list.col_status') }}</th>
                        @endif
                        @if (in_array('ai_status', $visibleColumns, true))
                            <th>{{ __('agricart.packing_units.list.col_ai_status') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('agricart.packing_units.list.col_created') }}</th>
                        @endif
                        <th>{{ __('agricart.packing_units.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($packingUnits as $packingUnit)
                        <tr wire:key="packing-unit-{{ $packingUnit->id }}" class="agricart-categories__row">
                            @if (in_array('code', $visibleColumns, true))
                                <td><code>{{ $packingUnit->code }}</code></td>
                            @endif
                            @if (in_array('name_en', $visibleColumns, true))
                                <td>{{ $packingUnit->name_en }}</td>
                            @endif
                            @if (in_array('name_ur', $visibleColumns, true))
                                <td dir="rtl">{{ $packingUnit->name_ur }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ ucfirst($packingUnit->status) }}</td>
                            @endif
                            @if (in_array('ai_status', $visibleColumns, true))
                                <td>{{ $packingUnit->ai_status?->label() ?? '—' }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $packingUnit->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $packingUnit->id }})">{{ __('agricart.packing_units.actions.view_short') }}</button>
                                <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $packingUnit->id }})">{{ __('agricart.packing_units.actions.edit_short') }}</button>
                                <button type="button" class="agricart-categories__action is-danger" wire:click="deletePackingUnit({{ $packingUnit->id }})" wire:confirm="{{ __('agricart.packing_units.actions.delete_confirm') }}">{{ __('agricart.packing_units.actions.delete_short') }}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $packingUnits->links() }}
        </div>
    @endif
</div>
