<div class="agricart-categories agricart-units">
    @if ($units->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.units.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('code', $visibleColumns, true))
                            <th>{{ __('agricart.units.list.col_code') }}</th>
                        @endif
                        @if (in_array('name_en', $visibleColumns, true))
                            <th>{{ __('agricart.units.list.col_name_en') }}</th>
                        @endif
                        @if (in_array('abbreviation_en', $visibleColumns, true))
                            <th>{{ __('agricart.units.list.col_abbreviation_en') }}</th>
                        @endif
                        @if (in_array('name_ur', $visibleColumns, true))
                            <th>{{ __('agricart.units.list.col_name_ur') }}</th>
                        @endif
                        @if (in_array('abbreviation_ur', $visibleColumns, true))
                            <th>{{ __('agricart.units.list.col_abbreviation_ur') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.units.list.col_status') }}</th>
                        @endif
                        @if (in_array('ai_status', $visibleColumns, true))
                            <th>{{ __('agricart.units.list.col_ai_status') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('agricart.units.list.col_created') }}</th>
                        @endif
                        <th>{{ __('agricart.units.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($units as $unit)
                        <tr wire:key="unit-{{ $unit->id }}" class="agricart-categories__row">
                            @if (in_array('code', $visibleColumns, true))
                                <td><code>{{ $unit->code }}</code></td>
                            @endif
                            @if (in_array('name_en', $visibleColumns, true))
                                <td>{{ $unit->name_en }}</td>
                            @endif
                            @if (in_array('abbreviation_en', $visibleColumns, true))
                                <td><code>{{ $unit->abbreviation_en }}</code></td>
                            @endif
                            @if (in_array('name_ur', $visibleColumns, true))
                                <td dir="rtl">{{ $unit->name_ur }}</td>
                            @endif
                            @if (in_array('abbreviation_ur', $visibleColumns, true))
                                <td dir="rtl"><code>{{ $unit->abbreviation_ur }}</code></td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ ucfirst($unit->status) }}</td>
                            @endif
                            @if (in_array('ai_status', $visibleColumns, true))
                                <td>{{ $unit->ai_status?->label() ?? '—' }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $unit->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $unit->id }})">{{ __('agricart.units.actions.view_short') }}</button>
                                <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $unit->id }})">{{ __('agricart.units.actions.edit_short') }}</button>
                                <button type="button" class="agricart-categories__action is-danger" wire:click="deleteUnit({{ $unit->id }})" wire:confirm="{{ __('agricart.units.actions.delete_confirm') }}">{{ __('agricart.units.actions.delete_short') }}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $units->links() }}
        </div>
    @endif
</div>
