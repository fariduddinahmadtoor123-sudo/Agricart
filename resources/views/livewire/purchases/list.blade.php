<div class="agricart-categories agricart-purchases">
    @if ($purchases->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('purchases.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('purchase_number', $visibleColumns, true))
                            <th>{{ __('purchases.list.col_number') }}</th>
                        @endif
                        @if (in_array('supplier', $visibleColumns, true))
                            <th>{{ __('purchases.list.col_supplier') }}</th>
                        @endif
                        @if (in_array('purchaser', $visibleColumns, true))
                            <th>{{ __('purchases.list.col_purchaser') }}</th>
                        @endif
                        @if (in_array('total_amount', $visibleColumns, true))
                            <th>{{ __('purchases.list.col_total') }}</th>
                        @endif
                        @if (in_array('delivery_status', $visibleColumns, true))
                            <th>{{ __('purchases.list.col_delivery') }}</th>
                        @endif
                        @if (in_array('payment_status', $visibleColumns, true))
                            <th>{{ __('purchases.list.col_payment') }}</th>
                        @endif
                        @if (in_array('dispute_status', $visibleColumns, true))
                            <th>{{ __('purchases.list.col_dispute') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('purchases.list.col_created') }}</th>
                        @endif
                        <th>{{ __('purchases.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchases as $purchase)
                        <tr wire:key="purchase-{{ $purchase->id }}" class="agricart-categories__row">
                            @if (in_array('purchase_number', $visibleColumns, true))
                                <td><code>{{ $purchase->purchase_number }}</code></td>
                            @endif
                            @if (in_array('supplier', $visibleColumns, true))
                                <td>{{ $purchase->supplier?->displayBusinessName() ?? '—' }}</td>
                            @endif
                            @if (in_array('purchaser', $visibleColumns, true))
                                <td>{{ $purchase->purchaser?->name ?? '—' }}</td>
                            @endif
                            @if (in_array('total_amount', $visibleColumns, true))
                                <td>{{ number_format((float) $purchase->total_amount, 2) }}</td>
                            @endif
                            @if (in_array('delivery_status', $visibleColumns, true))
                                <td>{{ __('purchases.statuses.delivery.' . $purchase->delivery_status) }}</td>
                            @endif
                            @if (in_array('payment_status', $visibleColumns, true))
                                <td>{{ __('purchases.statuses.payment.' . $purchase->payment_status) }}</td>
                            @endif
                            @if (in_array('dispute_status', $visibleColumns, true))
                                <td>
                                    <span @class([
                                        'agricart-purchases__dispute',
                                        'is-disputed' => $purchase->dispute_status === 'disputed',
                                    ])>
                                        {{ __('purchases.statuses.dispute.' . ($purchase->dispute_status ?? 'ok')) }}
                                    </span>
                                </td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $purchase->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $purchase->id }})">{{ __('purchases.actions.view_short') }}</button>
                                <button type="button" class="agricart-categories__action" wire:click="printPurchase({{ $purchase->id }})">{{ __('purchases.actions.print_short') }}</button>
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $purchase->id }})">{{ __('purchases.actions.edit_short') }}</button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="deletePurchase({{ $purchase->id }})" wire:confirm="{{ __('purchases.actions.delete_confirm') }}">{{ __('purchases.actions.delete_short') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $purchases->links() }}
        </div>
    @endif
</div>

@script
<script>
    Livewire.on('purchases-open-print', (payload) => {
        const url = payload?.url ?? payload?.[0]?.url;

        if (url) {
            window.open(url, '_blank');
        }
    });
</script>
@endscript
