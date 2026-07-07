<div class="agricart-categories">
    @if ($quotations->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('purchase_quotations.list.empty') }}</p>
            @if ($canCreate)
                <button type="button" class="agricart-categories__add" wire:click="openCreate">{{ __('purchase_quotations.actions.add') }}</button>
            @endif
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('quotation_number', $visibleColumns, true))
                            <th>{{ __('purchase_quotations.list.col_number') }}</th>
                        @endif
                        @if (in_array('supplier', $visibleColumns, true))
                            <th>{{ __('purchase_quotations.list.col_supplier') }}</th>
                        @endif
                        @if (in_array('item_count', $visibleColumns, true))
                            <th>{{ __('purchase_quotations.list.col_items') }}</th>
                        @endif
                        @if (in_array('locale', $visibleColumns, true))
                            <th>{{ __('purchase_quotations.list.col_locale') }}</th>
                        @endif
                        @if (in_array('created_by', $visibleColumns, true))
                            <th>{{ __('purchase_quotations.list.col_created_by') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('purchase_quotations.list.col_created') }}</th>
                        @endif
                        <th>{{ __('purchase_quotations.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quotations as $quotation)
                        <tr wire:key="quotation-{{ $quotation->id }}">
                            @if (in_array('quotation_number', $visibleColumns, true))
                                <td>{{ $quotation->quotation_number }}</td>
                            @endif
                            @if (in_array('supplier', $visibleColumns, true))
                                <td>{{ $quotation->supplier?->displayBusinessName() ?? '—' }}</td>
                            @endif
                            @if (in_array('item_count', $visibleColumns, true))
                                <td>{{ $quotation->items_count }}</td>
                            @endif
                            @if (in_array('locale', $visibleColumns, true))
                                <td>{{ __('purchase_quotations.locales.' . $quotation->displayLocale()) }}</td>
                            @endif
                            @if (in_array('created_by', $visibleColumns, true))
                                <td>{{ $quotation->creator?->name ?? '—' }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $quotation->created_at?->format('d M Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $quotation->id }})">{{ __('purchase_quotations.actions.view_short') }}</button>
                                <button type="button" class="agricart-categories__action" wire:click="printQuotation({{ $quotation->id }})">{{ __('purchase_quotations.actions.print_short') }}</button>
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $quotation->id }})">{{ __('purchase_quotations.actions.edit_short') }}</button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="deleteQuotation({{ $quotation->id }})" wire:confirm="{{ __('purchase_quotations.actions.delete_confirm') }}">{{ __('purchase_quotations.actions.delete_short') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $quotations->links() }}
        </div>
    @endif
</div>

@script
<script>
    Livewire.on('purchase-quotations-open-print', (payload) => {
        const url = payload?.url ?? payload?.[0]?.url;

        if (url) {
            window.open(url, '_blank');
        }
    });
</script>
@endscript
