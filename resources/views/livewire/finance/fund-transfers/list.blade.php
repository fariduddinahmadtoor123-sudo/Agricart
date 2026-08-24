<div class="agricart-categories agricart-finance-fund-transfers">
    @if ($transfers->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.finance.fund_transfers.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.finance.fund_transfers.list.col_number') }}</th>
                        <th>{{ __('agricart.finance.fund_transfers.list.col_date') }}</th>
                        <th>{{ __('agricart.finance.fund_transfers.list.col_type') }}</th>
                        <th>{{ __('agricart.finance.fund_transfers.sections.source') }}</th>
                        <th>{{ __('agricart.finance.fund_transfers.sections.destination') }}</th>
                        <th>{{ __('agricart.finance.fund_transfers.list.col_handled_by') }}</th>
                        <th>{{ __('agricart.finance.fund_transfers.list.col_amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transfers as $transfer)
                        <tr wire:key="fund-transfer-{{ $transfer->id }}" class="agricart-categories__row">
                            <td><code>{{ $transfer->transfer_number }}</code></td>
                            <td>{{ $transfer->transaction_date?->format('d M Y') }}</td>
                            <td>{{ $typeLabel($transfer) }}</td>
                            <td>{{ $sourceLabel($transfer) }}</td>
                            <td>{{ $destinationLabel($transfer) }}</td>
                            <td>{{ $transfer->handled_by_name ?: '—' }}</td>
                            <td><strong>{{ $money((float) $transfer->amount) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $transfers->links() }}
        </div>
    @endif
</div>
