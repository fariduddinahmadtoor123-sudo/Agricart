<div class="agricart-categories agricart-planning">
    @if ($sheets->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('purchase_planning.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('planning_number', $visibleColumns, true))
                            <th>{{ __('purchase_planning.list.col_number') }}</th>
                        @endif
                        @if (in_array('item_count', $visibleColumns, true))
                            <th>{{ __('purchase_planning.list.col_items') }}</th>
                        @endif
                        @if (in_array('paper_size', $visibleColumns, true))
                            <th>{{ __('purchase_planning.list.col_paper') }}</th>
                        @endif
                        @if (in_array('locale', $visibleColumns, true))
                            <th>{{ __('purchase_planning.list.col_locale') }}</th>
                        @endif
                        @if (in_array('created_by', $visibleColumns, true))
                            <th>{{ __('purchase_planning.list.col_created_by') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('purchase_planning.list.col_created') }}</th>
                        @endif
                        <th>{{ __('purchase_planning.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sheets as $sheet)
                        <tr wire:key="planning-sheet-{{ $sheet->id }}" class="agricart-categories__row">
                            @if (in_array('planning_number', $visibleColumns, true))
                                <td><code>{{ $sheet->planning_number }}</code></td>
                            @endif
                            @if (in_array('item_count', $visibleColumns, true))
                                <td>{{ $sheet->items->count() }}</td>
                            @endif
                            @if (in_array('paper_size', $visibleColumns, true))
                                <td>{{ __('purchase_planning.paper_sizes.' . $sheet->paper_size) }}</td>
                            @endif
                            @if (in_array('locale', $visibleColumns, true))
                                <td>{{ $sheet->localeLabel() }}</td>
                            @endif
                            @if (in_array('created_by', $visibleColumns, true))
                                <td>{{ $sheet->creator?->name ?? '—' }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $sheet->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $sheet->id }})">{{ __('purchase_planning.actions.view_short') }}</button>
                                <button type="button" class="agricart-categories__action" wire:click="printSheet({{ $sheet->id }})">{{ __('purchase_planning.actions.print_short') }}</button>
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $sheet->id }})">{{ __('purchase_planning.actions.edit_short') }}</button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="deleteSheet({{ $sheet->id }})" wire:confirm="{{ __('purchase_planning.actions.delete_confirm') }}">{{ __('purchase_planning.actions.delete_short') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $sheets->links() }}
        </div>
    @endif
</div>

@script
<script>
    Livewire.on('purchase-planning-open-print', (payload) => {
        const url = payload?.url ?? payload?.[0]?.url;

        if (url) {
            window.open(url, '_blank');
        }
    });
</script>
@endscript
