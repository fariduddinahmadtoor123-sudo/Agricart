<div class="agricart-categories agricart-staff-attendance">
    @if ($statusMessage)
        <div @class([
            'agricart-store-admin__status',
            'is-success' => $statusType === 'success',
            'is-error' => $statusType === 'error',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    @if ($publicHoliday)
        <div class="agricart-store-admin__alert agricart-staff-attendance__holiday-alert">
            {{ __('agricart.staff.attendance.hints.public_holiday', ['title' => $publicHoliday->title_en]) }}
        </div>
    @endif

    @if ($members->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.staff.attendance.list.empty_roster') }}</p>
            <p class="agricart-staff-members__empty-hint">{{ __('agricart.staff.attendance.list.empty_roster_hint') }}</p>
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
                            $statusClass = match (true) {
                                $statusKey === \App\Models\StaffAttendanceRecord::STATUS_PRESENT => 'is-attendance-present',
                                in_array($statusKey, [\App\Models\StaffAttendanceRecord::STATUS_ON_PAID_LEAVE, \App\Models\StaffAttendanceRecord::STATUS_PUBLIC_HOLIDAY], true) => 'is-attendance-leave',
                                $statusKey === \App\Models\StaffAttendanceRecord::STATUS_ABSENT_UNPAID => 'is-attendance-absent',
                                default => 'is-attendance-unmarked',
                            };
                        @endphp
                        <tr wire:key="att-{{ $member->id }}-{{ $attendanceDate }}" class="agricart-categories__row">
                            @if (in_array('user_number', $visibleColumns, true))
                                <td><code>{{ $member->user_number ?: '—' }}</code></td>
                            @endif
                            @if (in_array('name', $visibleColumns, true))
                                <td>
                                    {{ $member->name }}
                                    @if ($member->status === \App\Models\User::STATUS_INACTIVE)
                                        <span class="agricart-staff-members__status is-inactive agricart-staff-attendance__inline-badge">
                                            {{ __('agricart.users.statuses.inactive') }}
                                        </span>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('role', $visibleColumns, true))
                                <td>{{ $member->role?->name ?? '—' }}</td>
                            @endif
                            @if (in_array('attendance_status', $visibleColumns, true))
                                <td>
                                    <span @class(['agricart-staff-members__status', $statusClass])>
                                        {{ __('agricart.staff.attendance.statuses.' . ($statusKey === 'unmarked' ? 'unmarked' : $statusKey)) }}
                                    </span>
                                </td>
                            @endif
                            @if (in_array('paid_day', $visibleColumns, true))
                                <td>
                                    @if ($record)
                                        <span @class([
                                            'agricart-staff-members__status',
                                            'is-active' => $record->is_paid_day,
                                            'is-inactive' => ! $record->is_paid_day,
                                        ])>
                                            {{ $record->is_paid_day ? __('agricart.staff.attendance.paid.yes') : __('agricart.staff.attendance.paid.no') }}
                                        </span>
                                    @else
                                        <span class="agricart-staff-pay__muted">—</span>
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
