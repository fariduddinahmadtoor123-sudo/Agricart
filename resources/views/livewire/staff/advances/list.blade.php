<div class="agricart-categories agricart-staff-advances">
    @if ($statusMessage)
        <div @class([
            'agricart-store-admin__status',
            'is-success' => $statusType === 'success',
            'is-error' => $statusType === 'error',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    @if ($members->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.staff.advances.list.empty_balances') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('user_number', $visibleColumns, true))
                            <th>{{ __('agricart.staff.members.list.col_user_number') }}</th>
                        @endif
                        @if (in_array('name', $visibleColumns, true))
                            <th>{{ __('agricart.staff.members.list.col_name') }}</th>
                        @endif
                        @if (in_array('balance_side', $visibleColumns, true))
                            <th>{{ __('agricart.staff.advances.list.col_balance_side') }}</th>
                        @endif
                        @if (in_array('balance_amount', $visibleColumns, true))
                            <th>{{ __('agricart.staff.advances.list.col_balance_amount') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $member)
                        @php
                            $row = $balances->get($member->id);
                            $raw = (float) ($row['balance'] ?? 0);
                            $owesUs = (float) ($row['owes_us'] ?? 0);
                            $weOwe = (float) ($row['we_owe'] ?? 0);
                        @endphp
                        <tr wire:key="adv-bal-{{ $member->id }}" class="agricart-categories__row">
                            @if (in_array('user_number', $visibleColumns, true))
                                <td><code>{{ $member->user_number ?: '—' }}</code></td>
                            @endif
                            @if (in_array('name', $visibleColumns, true))
                                <td>{{ $member->name }}</td>
                            @endif
                            @if (in_array('balance_side', $visibleColumns, true))
                                <td>
                                    @if ($owesUs > 0)
                                        <span class="agricart-staff-advances__side is-debit">{{ __('agricart.staff.advances.list.side_debit') }}</span>
                                    @elseif ($weOwe > 0)
                                        <span class="agricart-staff-advances__side is-credit">{{ __('agricart.staff.advances.list.side_credit') }}</span>
                                    @else
                                        <span class="agricart-staff-pay__muted">{{ __('agricart.staff.advances.list.side_clear') }}</span>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('balance_amount', $visibleColumns, true))
                                <td>
                                    @if ($raw > 0)
                                        <strong class="agricart-staff-advances__amount is-debit">{{ $money($owesUs) }}</strong>
                                    @elseif ($raw < 0)
                                        <strong class="agricart-staff-advances__amount is-credit">{{ $money($weOwe) }}</strong>
                                    @else
                                        <span class="agricart-staff-pay__muted">0</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
