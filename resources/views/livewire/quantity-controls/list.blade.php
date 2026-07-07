<div class="agricart-categories agricart-quantity-controls">
    @if (session('quantity_control_error'))
        <div class="agricart-user-form__alert is-danger">{{ session('quantity_control_error') }}</div>
    @endif

    @if ($records->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('quantity_controls.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('control_number', $visibleColumns, true))
                            <th>{{ __('quantity_controls.list.col_number') }}</th>
                        @endif
                        @if (in_array('name_en', $visibleColumns, true))
                            <th>{{ __('quantity_controls.list.col_name_en') }}</th>
                        @endif
                        @if (in_array('name_ur', $visibleColumns, true))
                            <th>{{ __('quantity_controls.list.col_name_ur') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('quantity_controls.list.col_status') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('quantity_controls.list.col_created') }}</th>
                        @endif
                        <th>{{ __('quantity_controls.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($records as $record)
                        <tr wire:key="quantity-control-{{ $record->id }}" class="agricart-categories__row">
                            @if (in_array('control_number', $visibleColumns, true))
                                <td><code>{{ $record->control_number }}</code></td>
                            @endif
                            @if (in_array('name_en', $visibleColumns, true))
                                <td>{{ $record->name_en }}</td>
                            @endif
                            @if (in_array('name_ur', $visibleColumns, true))
                                <td>{{ $record->name_ur }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ __('quantity_controls.statuses.' . $record->status) }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $record->created_at?->format('Y-m-d H:i') }}</td>
                            @endif
                            <td>
                                <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $record->id }})">
                                    {{ __('quantity_controls.actions.edit_short') }}
                                </button>
                                <button
                                    type="button"
                                    class="agricart-categories__action is-danger"
                                    wire:click="deleteRecord({{ $record->id }})"
                                    wire:confirm="{{ __('quantity_controls.messages.delete_confirm', ['name' => $record->name_en]) }}"
                                >
                                    {{ __('quantity_controls.actions.delete_short') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $records->links() }}
        </div>
    @endif
</div>
