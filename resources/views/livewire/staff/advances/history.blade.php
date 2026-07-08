<div class="agricart-categories agricart-staff-advances-history">
    <h3 class="agricart-staff-advances__section-title">{{ __('agricart.staff.advances.list.history_heading') }}</h3>

    @if ($transactions->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.staff.advances.list.empty_history') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('transaction_date', $visibleColumns, true))
                            <th>{{ __('agricart.staff.advances.list.col_date') }}</th>
                        @endif
                        @if (in_array('user_number', $visibleColumns, true))
                            <th>{{ __('agricart.staff.members.list.col_user_number') }}</th>
                        @endif
                        @if (in_array('name', $visibleColumns, true))
                            <th>{{ __('agricart.staff.members.list.col_name') }}</th>
                        @endif
                        @if (in_array('type', $visibleColumns, true))
                            <th>{{ __('agricart.staff.advances.list.col_type') }}</th>
                        @endif
                        @if (in_array('amount', $visibleColumns, true))
                            <th>{{ __('agricart.staff.advances.list.col_amount') }}</th>
                        @endif
                        @if (in_array('balance', $visibleColumns, true))
                            <th>{{ __('agricart.staff.advances.list.col_balance') }}</th>
                        @endif
                        @if (in_array('notes', $visibleColumns, true))
                            <th>{{ __('agricart.staff.advances.list.col_notes') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transactions as $transaction)
                        @php
                            $signed = $transaction->isAdvance() ? (float) $transaction->amount : -(float) $transaction->amount;
                            $bal = $balanceAfter[$transaction->id] ?? 0;
                        @endphp
                        <tr wire:key="adv-tx-{{ $transaction->id }}" class="agricart-categories__row">
                            @if (in_array('transaction_date', $visibleColumns, true))
                                <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                            @endif
                            @if (in_array('user_number', $visibleColumns, true))
                                <td><code>{{ $transaction->user?->user_number ?: '—' }}</code></td>
                            @endif
                            @if (in_array('name', $visibleColumns, true))
                                <td>{{ $transaction->user?->name ?? '—' }}</td>
                            @endif
                            @if (in_array('type', $visibleColumns, true))
                                <td>
                                    <span @class([
                                        'agricart-staff-members__status',
                                        'is-attendance-leave' => $transaction->isAdvance(),
                                        'is-attendance-present' => $transaction->isRepayment(),
                                    ])>
                                        {{ __('agricart.staff.advances.types.' . $transaction->type) }}
                                    </span>
                                </td>
                            @endif
                            @if (in_array('amount', $visibleColumns, true))
                                <td>
                                    <span @class([
                                        'agricart-staff-advances__signed',
                                        'is-plus' => $signed > 0,
                                        'is-minus' => $signed < 0,
                                    ])>
                                        {{ $signed > 0 ? '+' : '' }}{{ number_format($signed, 0) }}
                                    </span>
                                </td>
                            @endif
                            @if (in_array('balance', $visibleColumns, true))
                                <td>
                                    @if ($bal > 0)
                                        <span class="agricart-staff-advances__amount is-debit">{{ __('agricart.staff.advances.list.owes_short', ['amount' => number_format($bal, 0)]) }}</span>
                                    @elseif ($bal < 0)
                                        <span class="agricart-staff-advances__amount is-credit">{{ __('agricart.staff.advances.list.we_owe_short', ['amount' => number_format(abs($bal), 0)]) }}</span>
                                    @else
                                        <span class="agricart-staff-pay__muted">0</span>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('notes', $visibleColumns, true))
                                <td>{{ $transaction->notes ?: '—' }}</td>
                            @endif
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
