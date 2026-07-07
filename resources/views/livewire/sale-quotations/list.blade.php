<div class="agricart-categories agricart-purchases">
    @if ($quotations->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('sale_quotations.list.empty') }}</p>
            @if ($canCreate)
                <button type="button" class="agricart-categories__add" wire:click="openCreate">{{ __('sale_quotations.actions.add') }}</button>
            @endif
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('quotation_number', $visibleColumns, true))
                            <th>{{ __('sale_quotations.list.col_number') }}</th>
                        @endif
                        @if (in_array('customer', $visibleColumns, true))
                            <th>{{ __('sale_quotations.list.col_customer') }}</th>
                        @endif
                        @if (in_array('prepared_by', $visibleColumns, true))
                            <th>{{ __('sale_quotations.list.col_prepared_by') }}</th>
                        @endif
                        @if (in_array('quotation_amount', $visibleColumns, true))
                            <th>{{ __('sale_quotations.list.col_amount') }}</th>
                        @endif
                        @if (in_array('locale', $visibleColumns, true))
                            <th>{{ __('sale_quotations.list.col_locale') }}</th>
                        @endif
                        @if (in_array('quotation_date', $visibleColumns, true))
                            <th>{{ __('sale_quotations.list.col_quotation_date') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('sale_quotations.list.col_created') }}</th>
                        @endif
                        <th>{{ __('sale_quotations.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quotations as $quotation)
                        <tr wire:key="sale-quotation-{{ $quotation->id }}">
                            @if (in_array('quotation_number', $visibleColumns, true))
                                <td><code>{{ $quotation->quotation_number }}</code></td>
                            @endif
                            @if (in_array('customer', $visibleColumns, true))
                                <td>{{ $quotation->customer?->displayLabel() ?? '—' }}</td>
                            @endif
                            @if (in_array('prepared_by', $visibleColumns, true))
                                <td>{{ $quotation->preparedBy?->name ?? '—' }}</td>
                            @endif
                            @if (in_array('quotation_amount', $visibleColumns, true))
                                <td>{{ number_format((float) $quotation->quotation_amount, 2) }}</td>
                            @endif
                            @if (in_array('locale', $visibleColumns, true))
                                <td>{{ __('sale_quotations.locales.' . $quotation->displayLocale()) }}</td>
                            @endif
                            @if (in_array('quotation_date', $visibleColumns, true))
                                <td>{{ $quotation->quotation_date?->format('M j, Y') }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $quotation->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $quotation->id }})">{{ __('sale_quotations.actions.view_short') }}</button>
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $quotation->id }})">{{ __('sale_quotations.actions.edit_short') }}</button>
                                @endif
                                <button type="button" class="agricart-categories__action" wire:click="printQuotation({{ $quotation->id }})">{{ __('sale_quotations.actions.print_short') }}</button>
                                @if ($canDelete)
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="deleteQuotation({{ $quotation->id }})" wire:confirm="{{ __('sale_quotations.actions.delete_confirm') }}">{{ __('sale_quotations.actions.delete_short') }}</button>
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
    Livewire.on('sale-quotations-open-print', (payload) => {
        const url = payload?.url ?? payload?.[0]?.url;

        if (url) {
            window.open(url, '_blank');
        }
    });
</script>
@endscript
