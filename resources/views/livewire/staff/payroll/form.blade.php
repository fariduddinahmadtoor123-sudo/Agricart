<div
    class="agricart-user-form agricart-staff-payroll-form"
    id="agricart-staff-payroll-print-root"
    x-on:staff-payroll-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:staff-payroll-form-open.window="$wire.openForm($event.detail.payrollMonth ?? null)"
    x-on:staff-payroll-print.window="window.print()"
>
    @if (! $canRun)
        <p>{{ __('agricart.access.denied') }}</p>
    @else
        <div class="agricart-staff-payroll-form__controls no-print">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="staff-payroll-month">{{ __('agricart.staff.payroll.fields.month') }}</label>
                <input id="staff-payroll-month" type="month" wire:model.live="payrollMonth" class="agricart-purchase-form__input agricart-staff-payroll-form__month-input" />
            </div>
            @if ($canViewAll)
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="staff-payroll-user">{{ __('agricart.staff.payroll.fields.staff_filter') }}</label>
                    <select id="staff-payroll-user" wire:model.live="userId" class="fi-select-input">
                        <option value="">{{ __('agricart.staff.payroll.fields.all_staff') }}</option>
                        @foreach ($rosterOptions as $option)
                            <option value="{{ $option->id }}">{{ $option->name }} ({{ $option->user_number }})</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <button type="button" class="agricart-categories__add" wire:click="generateRoll">{{ __('agricart.staff.payroll.actions.regenerate') }}</button>
        </div>

        @if (! $generated || $rolls === [])
            <p class="agricart-staff-pay__muted">{{ __('agricart.staff.payroll.form.empty') }}</p>
        @else
            @foreach ($rolls as $roll)
                @php
                    /** @var \App\Models\User $member */
                    $member = $roll['member'];
                @endphp
                <article class="agricart-staff-payroll-form__slip" wire:key="payroll-slip-{{ $member->id }}-{{ $payrollMonth }}">
                    <header class="agricart-staff-payroll-form__slip-header">
                        <h2>{{ __('agricart.staff.payroll.form.slip_title', ['name' => $member->name]) }}</h2>
                        <p>{{ __('agricart.staff.payroll.form.slip_period', [
                            'month' => $monthLabel,
                            'from' => \Carbon\Carbon::parse($roll['period_from'])->format('d M Y'),
                            'to' => \Carbon\Carbon::parse($roll['period_to'])->format('d M Y'),
                        ]) }}</p>
                        <p><code>{{ $member->user_number }}</code></p>
                    </header>

                    <table class="agricart-staff-payroll-form__summary-table">
                        <tbody>
                            <tr>
                                <th>{{ __('agricart.staff.payroll.list.col_monthly_gross') }}</th>
                                <td>{{ $roll['has_salary'] ? $money((float) $roll['monthly_gross']) : '—' }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('agricart.staff.payroll.list.col_paid_days') }}</th>
                                <td>{{ $roll['paid_days'] }} / {{ $roll['days_in_month'] }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('agricart.staff.payroll.list.col_earned') }}</th>
                                <td>{{ $roll['has_salary'] ? $money((float) $roll['earned_gross']) : '—' }}</td>
                            </tr>
                            <tr>
                                <th>{{ __('agricart.staff.payroll.list.col_advance') }}</th>
                                <td>
                                    @php $bal = (float) $roll['advance_balance']; @endphp
                                    @if ($bal > 0)
                                        {{ __('agricart.staff.advances.list.side_debit') }} {{ $money($bal) }}
                                    @elseif ($bal < 0)
                                        {{ __('agricart.staff.advances.list.side_credit') }} {{ $money(abs($bal)) }}
                                    @else
                                        0
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>{{ __('agricart.staff.payroll.list.col_net') }}</th>
                                <td><strong>{{ $roll['has_salary'] ? $money((float) $roll['net_payable']) : '—' }}</strong></td>
                            </tr>
                        </tbody>
                    </table>

                    @if (! empty($roll['leave_rows']))
                        <h3 class="agricart-staff-payroll-form__subheading">{{ __('agricart.staff.payroll.form.leave_detail') }}</h3>
                        <table class="agricart-categories__table agricart-staff-payroll-form__detail-table">
                            <thead>
                                <tr>
                                    <th>{{ __('agricart.staff.leave.list.col_date') }}</th>
                                    <th>{{ __('agricart.staff.attendance.list.col_status') }}</th>
                                    <th>{{ __('agricart.staff.attendance.list.col_paid_day') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roll['leave_rows'] as $leave)
                                    <tr>
                                        <td dir="ltr">{{ $leave['date'] }}</td>
                                        <td>{{ __('agricart.staff.attendance.statuses.' . $leave['status']) }}</td>
                                        <td>{{ $leave['is_paid_day'] ? __('agricart.staff.attendance.paid.yes') : __('agricart.staff.attendance.paid.no') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    @if (! empty($roll['advance_rows']))
                        <h3 class="agricart-staff-payroll-form__subheading">{{ __('agricart.staff.payroll.form.advance_detail') }}</h3>
                        <table class="agricart-categories__table agricart-staff-payroll-form__detail-table">
                            <thead>
                                <tr>
                                    <th>{{ __('agricart.staff.advances.list.col_date') }}</th>
                                    <th>{{ __('agricart.staff.advances.list.col_type') }}</th>
                                    <th>{{ __('agricart.staff.advances.list.col_amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roll['advance_rows'] as $adv)
                                    @php
                                        $signed = $adv['type'] === \App\Models\StaffAdvanceTransaction::TYPE_ADVANCE
                                            ? (float) $adv['amount']
                                            : -(float) $adv['amount'];
                                    @endphp
                                    <tr>
                                        <td dir="ltr">{{ $adv['date'] }}</td>
                                        <td>{{ __('agricart.staff.advances.types.' . $adv['type']) }}</td>
                                        <td>{{ $signed > 0 ? '+' : '' }}{{ number_format($signed, 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </article>
            @endforeach
        @endif
    @endif
</div>
