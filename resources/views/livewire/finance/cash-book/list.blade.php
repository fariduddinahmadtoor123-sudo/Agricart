<div class="agricart-categories agricart-finance-cash-book">
    <div class="agricart-categories__empty" style="margin-bottom: 1rem;">
        <p>
            <strong>{{ __('agricart.finance.cash_book.balance_label') }}:</strong>
            {{ $money($balance) }}
        </p>
    </div>

    @if ($transactions->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.finance.cash_book.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.finance.cash_book.list.col_date') }}</th>
                        <th>{{ __('agricart.finance.cash_book.list.col_direction') }}</th>
                        <th>{{ __('agricart.finance.cash_book.list.col_description') }}</th>
                        <th>{{ __('agricart.finance.cash_book.list.col_amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transactions as $row)
                        <tr wire:key="cash-book-{{ $row->id }}" class="agricart-categories__row">
                            <td>{{ $row->transaction_date?->format('d M Y') }}</td>
                            <td>
                                {{ $row->direction === \App\Models\CashBookTransaction::DIRECTION_IN
                                    ? __('agricart.finance.cash_book.directions.in')
                                    : __('agricart.finance.cash_book.directions.out') }}
                            </td>
                            <td>{{ $row->description ?: '—' }}</td>
                            <td><strong>{{ $money((float) $row->amount) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
