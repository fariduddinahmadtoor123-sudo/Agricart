<div class="agricart-categories agricart-staff-leave-report">
    @if ($statusMessage ?? false)
        <div class="agricart-categories__status is-{{ $statusType ?? 'success' }}">
            {{ $statusMessage }}
        </div>
    @endif

    <div class="agricart-staff-leave-report__summary-wrap">
        <table class="agricart-categories__table agricart-staff-leave-report__summary-table">
            <thead>
                <tr>
                    <th>{{ __('agricart.staff.leave.report.col_date') }}</th>
                    <th>{{ __('agricart.staff.attendance.statuses.present') }}</th>
                    <th>{{ __('agricart.staff.leave.report.col_on_leave') }}</th>
                    <th>{{ __('agricart.staff.leave.report.col_absent') }}</th>
                    <th>{{ __('agricart.staff.leave.report.col_holiday') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($daySummaries as $day)
                    <tr
                        wire:key="leave-day-{{ $day['date'] }}"
                        @class([
                            'agricart-staff-leave-report__day-row',
                            'is-selected' => $day['is_selected'],
                        ])
                        wire:click="selectDay('{{ $day['date'] }}')"
                        role="button"
                        tabindex="0"
                    >
                        <td>
                            <strong>{{ $day['label'] }}</strong>
                            @if ($day['is_selected'])
                                <span class="agricart-staff-leave-report__selected-badge">{{ __('agricart.staff.leave.report.selected_day') }}</span>
                            @endif
                        </td>
                        <td>{{ $day['summary']['present'] }}</td>
                        <td>{{ $day['summary']['leave'] }}</td>
                        <td>{{ $day['summary']['absent'] }}</td>
                        <td>
                            @if ($day['public_holiday'])
                                {{ $day['public_holiday']->title_en }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h3 class="agricart-staff-leave-report__section-title">
        {{ __('agricart.staff.leave.report.detail_heading', ['date' => $detailDate->translatedFormat('j M Y')]) }}
    </h3>

    @if ($publicHoliday)
        <p class="agricart-staff-leave-report__holiday-banner">
            {{ __('agricart.staff.attendance.hints.public_holiday', ['title' => $publicHoliday->title_en]) }}
        </p>
    @endif

    @if ($members->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.staff.leave.report.empty_day') }}</p>
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
                        @if (in_array('role', $visibleColumns, true))
                            <th>{{ __('agricart.staff.members.list.col_role') }}</th>
                        @endif
                        @if (in_array('attendance_status', $visibleColumns, true))
                            <th>{{ __('agricart.staff.attendance.list.col_status') }}</th>
                        @endif
                        @if (in_array('paid_day', $visibleColumns, true))
                            <th>{{ __('agricart.staff.attendance.list.col_paid_day') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $member)
                        @php
                            $record = $records->get($member->id);
                            $statusKey = $record?->status ?? 'unmarked';
                        @endphp
                        <tr wire:key="leave-member-{{ $member->id }}-{{ $detailDate->toDateString() }}">
                            @if (in_array('user_number', $visibleColumns, true))
                                <td dir="ltr">{{ $member->user_number }}</td>
                            @endif
                            @if (in_array('name', $visibleColumns, true))
                                <td>{{ $member->name }}</td>
                            @endif
                            @if (in_array('role', $visibleColumns, true))
                                <td>{{ $member->role?->name ?? '—' }}</td>
                            @endif
                            @if (in_array('attendance_status', $visibleColumns, true))
                                <td>
                                    <span @class([
                                        'agricart-staff-members__status-pill',
                                        'is-attendance-present' => $statusKey === \App\Models\StaffAttendanceRecord::STATUS_PRESENT,
                                        'is-attendance-leave' => in_array($statusKey, [\App\Models\StaffAttendanceRecord::STATUS_ON_PAID_LEAVE, \App\Models\StaffAttendanceRecord::STATUS_PUBLIC_HOLIDAY], true),
                                        'is-attendance-absent' => $statusKey === \App\Models\StaffAttendanceRecord::STATUS_ABSENT_UNPAID,
                                    ])>
                                        {{ __('agricart.staff.attendance.statuses.' . ($statusKey === 'unmarked' ? 'unmarked' : $statusKey)) }}
                                    </span>
                                </td>
                            @endif
                            @if (in_array('paid_day', $visibleColumns, true))
                                <td>
                                    @if ($record)
                                        {{ $record->is_paid_day ? __('agricart.staff.attendance.paid.yes') : __('agricart.staff.attendance.paid.no') }}
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
    @endif
</div>
