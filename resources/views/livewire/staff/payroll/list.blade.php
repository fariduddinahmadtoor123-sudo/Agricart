<div class="agricart-categories agricart-staff-payroll">
    @if ($members->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.staff.payroll.list.empty') }}</p>
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
                        @if (in_array('monthly_gross', $visibleColumns, true))
                            <th>{{ __('agricart.staff.payroll.list.col_monthly_gross') }}</th>
                        @endif
                        @if (in_array('paid_days', $visibleColumns, true))
                            <th>{{ __('agricart.staff.payroll.list.col_paid_days') }}</th>
                        @endif
                        @if (in_array('earned_gross', $visibleColumns, true))
                            <th>{{ __('agricart.staff.payroll.list.col_earned') }}</th>
                        @endif
                        @if (in_array('advance_net', $visibleColumns, true))
                            <th>{{ __('agricart.staff.payroll.list.col_advance') }}</th>
                        @endif
                        @if (in_array('net_payable', $visibleColumns, true))
                            <th>{{ __('agricart.staff.payroll.list.col_net') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $member)
                        @php
                            $row = $rows[$member->id] ?? null;
                        @endphp
                        <tr wire:key="payroll-{{ $member->id }}-{{ $payrollMonth }}" class="agricart-categories__row">
                            @if (in_array('user_number', $visibleColumns, true))
                                <td><code>{{ $member->user_number ?: '—' }}</code></td>
                            @endif
                            @if (in_array('name', $visibleColumns, true))
                                <td>{{ $member->name }}</td>
                            @endif
                            @if (in_array('monthly_gross', $visibleColumns, true))
                                <td>
                                    @if ($row && $row['has_salary'])
                                        {{ $money((float) $row['monthly_gross']) }}
                                    @else
                                        <span class="agricart-staff-pay__muted">—</span>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('paid_days', $visibleColumns, true))
                                <td>
                                    @if ($row)
                                        {{ $row['paid_days'] }}/{{ $row['days_in_month'] }}
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                            @if (in_array('earned_gross', $visibleColumns, true))
                                <td>
                                    @if ($row && $row['has_salary'])
                                        {{ $money((float) $row['earned_gross']) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                            @if (in_array('advance_net', $visibleColumns, true))
                                <td>
                                    @if ($row)
                                        @php
                                            $bal = (float) $row['advance_balance'];
                                        @endphp
                                        @if ($bal > 0)
                                            <span class="agricart-staff-advances__amount is-debit">{{ __('agricart.staff.advances.list.side_debit') }} {{ $money($bal) }}</span>
                                        @elseif ($bal < 0)
                                            <span class="agricart-staff-advances__amount is-credit">{{ __('agricart.staff.advances.list.side_credit') }} {{ $money(abs($bal)) }}</span>
                                        @else
                                            <span class="agricart-staff-pay__muted">0</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                            @if (in_array('net_payable', $visibleColumns, true))
                                <td>
                                    @if ($row && $row['has_salary'])
                                        <strong>{{ $money((float) $row['net_payable']) }}</strong>
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($members->hasPages())
            <div class="mt-4">{{ $members->links() }}</div>
        @endif
    @endif
</div>
