<div class="agricart-categories agricart-payment-summaries">
    @if ($summaries->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('purchase_payment_summary.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('summary_number', $visibleColumns, true))
                            <th>{{ __('purchase_payment_summary.list.col_number') }}</th>
                        @endif
                        @if (in_array('summary_date', $visibleColumns, true))
                            <th>{{ __('purchase_payment_summary.list.col_date') }}</th>
                        @endif
                        @if (in_array('paid_lines', $visibleColumns, true))
                            <th>{{ __('purchase_payment_summary.list.col_paid_lines') }}</th>
                        @endif
                        @if (in_array('total_payments', $visibleColumns, true))
                            <th>{{ __('purchase_payment_summary.list.col_total') }}</th>
                        @endif
                        @if (in_array('locale', $visibleColumns, true))
                            <th>{{ __('purchase_payment_summary.list.col_locale') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('purchase_payment_summary.list.col_created') }}</th>
                        @endif
                        <th>{{ __('purchase_payment_summary.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($summaries as $summary)
                        <tr wire:key="payment-summary-{{ $summary->id }}" class="agricart-categories__row">
                            @if (in_array('summary_number', $visibleColumns, true))
                                <td><code>{{ $summary->summary_number }}</code></td>
                            @endif
                            @if (in_array('summary_date', $visibleColumns, true))
                                <td>{{ $summary->summary_date?->format('d M Y') }}</td>
                            @endif
                            @if (in_array('paid_lines', $visibleColumns, true))
                                <td>{{ $summary->paidLineCount() }}</td>
                            @endif
                            @if (in_array('total_payments', $visibleColumns, true))
                                <td>{{ number_format($summary->totalPayments(), 0) }}</td>
                            @endif
                            @if (in_array('locale', $visibleColumns, true))
                                <td>{{ $summary->localeLabel() }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $summary->created_at?->format('d M Y H:i') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $summary->id }})">{{ __('purchase_payment_summary.actions.view_short') }}</button>
                                <button type="button" class="agricart-categories__action" wire:click="printSummary({{ $summary->id }})">{{ __('purchase_payment_summary.actions.print_short') }}</button>
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $summary->id }})">{{ __('purchase_payment_summary.actions.edit_short') }}</button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="deleteSummary({{ $summary->id }})" wire:confirm="{{ __('purchase_payment_summary.actions.delete_confirm') }}">{{ __('purchase_payment_summary.actions.delete_short') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $summaries->links() }}
        </div>
    @endif
</div>

@script
<script>
    Livewire.on('purchase-payment-summary-open-print', (payload) => {
        const url = payload?.url ?? payload?.[0]?.url;
        if (url) window.open(url, '_blank');
    });
</script>
@endscript
