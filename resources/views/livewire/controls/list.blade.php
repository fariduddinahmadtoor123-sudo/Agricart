<div class="agricart-categories agricart-controls">
    @if ($controls->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.controls.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('code', $visibleColumns, true))
                            <th>{{ __('agricart.controls.list.col_code') }}</th>
                        @endif
                        @if (in_array('name_en', $visibleColumns, true))
                            <th>{{ __('agricart.controls.list.col_name_en') }}</th>
                        @endif
                        @if (in_array('name_ur', $visibleColumns, true))
                            <th>{{ __('agricart.controls.list.col_name_ur') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.controls.list.col_status') }}</th>
                        @endif
                        @if (in_array('ai_status', $visibleColumns, true))
                            <th>{{ __('agricart.controls.list.col_ai_status') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('agricart.controls.list.col_created') }}</th>
                        @endif
                        <th>{{ __('agricart.controls.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($controls as $control)
                        <tr wire:key="control-{{ $control->id }}" class="agricart-categories__row">
                            @if (in_array('code', $visibleColumns, true))
                                <td><code>{{ $control->code }}</code></td>
                            @endif
                            @if (in_array('name_en', $visibleColumns, true))
                                <td>{{ $control->name_en }}</td>
                            @endif
                            @if (in_array('name_ur', $visibleColumns, true))
                                <td dir="rtl">{{ $control->name_ur }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ ucfirst($control->status) }}</td>
                            @endif
                            @if (in_array('ai_status', $visibleColumns, true))
                                <td>{{ $control->ai_status?->label() ?? '—' }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $control->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                @if ($canView)<button type="button" class="agricart-categories__action" wire:click="openView({{ $control->id }})">{{ __('agricart.controls.actions.view_short') }}</button>@endif
                                @if ($canEdit)<button type="button" class="agricart-categories__action" wire:click="openEdit({{ $control->id }})">{{ __('agricart.controls.actions.edit_short') }}</button>@endif
                                @if ($canDelete)<button type="button" class="agricart-categories__action is-danger" wire:click="deleteControl({{ $control->id }})" wire:confirm="{{ __('agricart.controls.actions.delete_confirm') }}">{{ __('agricart.controls.actions.delete_short') }}</button>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $controls->links() }}
        </div>
    @endif
</div>
