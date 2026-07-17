<div class="agricart-categories agricart-finance-expense-payments">
    @if ($payments->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.finance.expense_payments.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('payment_number', $visibleColumns, true))
                            <th>{{ __('agricart.finance.expense_payments.list.col_number') }}</th>
                        @endif
                        @if (in_array('date', $visibleColumns, true))
                            <th>{{ __('agricart.finance.expense_payments.list.col_date') }}</th>
                        @endif
                        @if (in_array('expense', $visibleColumns, true))
                            <th>{{ __('agricart.finance.expense_payments.list.col_expense') }}</th>
                        @endif
                        @if (in_array('category', $visibleColumns, true))
                            <th>{{ __('agricart.finance.expense_payments.list.col_category') }}</th>
                        @endif
                        @if (in_array('amount', $visibleColumns, true))
                            <th>{{ __('agricart.finance.expense_payments.list.col_amount') }}</th>
                        @endif
                        @if (in_array('wallet', $visibleColumns, true))
                            <th>{{ __('agricart.finance.expense_payments.list.col_wallet') }}</th>
                        @endif
                        <th>{{ __('agricart.finance.expense_payments.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                        <tr wire:key="expense-payment-{{ $payment->id }}" class="agricart-categories__row">
                            @if (in_array('payment_number', $visibleColumns, true))
                                <td><code>{{ $payment->payment_number }}</code></td>
                            @endif
                            @if (in_array('date', $visibleColumns, true))
                                <td>{{ $payment->payment_date?->format('Y-m-d') ?? '—' }}</td>
                            @endif
                            @if (in_array('expense', $visibleColumns, true))
                                <td>{{ $payment->expense?->displayName() ?? '—' }}</td>
                            @endif
                            @if (in_array('category', $visibleColumns, true))
                                <td>{{ $payment->expense?->category?->displayName() ?? '—' }}</td>
                            @endif
                            @if (in_array('amount', $visibleColumns, true))
                                <td class="is-numeric"><strong>{{ $money((float) $payment->amount) }}</strong></td>
                            @endif
                            @if (in_array('wallet', $visibleColumns, true))
                                <td>{{ __('agricart.finance.expense_payments.wallets.'.$payment->source_wallet) }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $payment->id }})">
                                    {{ __('agricart.finance.expense_payments.actions.edit_short') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $payments->links() }}
        </div>
    @endif
</div>
