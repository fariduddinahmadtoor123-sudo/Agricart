<div
    class="agricart-user-form agricart-staff-attendance-form"
    x-on:staff-attendance-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:staff-attendance-form-open.window="$wire.openForm($event.detail.attendanceDate ?? null)"
>
    @if ($statusMessage)
        <div @class([
            'agricart-user-form__alert',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <section class="agricart-form-panel__section">
        @if ($publicHoliday)
            <div class="agricart-staff-attendance__holiday-banner">
                {{ __('agricart.staff.attendance.hints.public_holiday_form', ['title' => $publicHoliday->title_en]) }}
            </div>
        @endif

        <div class="agricart-categories__table-wrap agricart-staff-attendance-form__table">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.staff.attendance.list.col_name') }}</th>
                        <th>{{ __('agricart.staff.attendance.list.col_leaves_left') }}</th>
                        <th>{{ __('agricart.staff.attendance.list.col_status') }}</th>
                        <th>{{ __('agricart.staff.attendance.list.col_notes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $index => $row)
                        <tr wire:key="att-row-{{ $row['user_id'] }}">
                            <td>
                                {{ $row['name'] }}
                                @if ($row['user_number'])
                                    <code>{{ $row['user_number'] }}</code>
                                @endif
                            </td>
                            <td>{{ $row['remaining_leaves'] ?? '—' }}</td>
                            <td>
                                <select wire:model="rows.{{ $index }}.status" class="fi-select-input">
                                    <option value="{{ \App\Models\StaffAttendanceRecord::STATUS_PRESENT }}">{{ __('agricart.staff.attendance.options.present') }}</option>
                                    <option value="absent">{{ __('agricart.staff.attendance.options.absent_auto_leave') }}</option>
                                    <option value="{{ \App\Models\StaffAttendanceRecord::STATUS_ON_PAID_LEAVE }}">{{ __('agricart.staff.attendance.options.paid_leave') }}</option>
                                    <option value="{{ \App\Models\StaffAttendanceRecord::STATUS_ABSENT_UNPAID }}">{{ __('agricart.staff.attendance.options.absent_unpaid') }}</option>
                                    @if ($publicHoliday)
                                        <option value="{{ \App\Models\StaffAttendanceRecord::STATUS_PUBLIC_HOLIDAY }}">{{ __('agricart.staff.attendance.options.public_holiday') }}</option>
                                    @endif
                                </select>
                            </td>
                            <td>
                                <input type="text" wire:model="rows.{{ $index }}.notes" class="fi-input" placeholder="—" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
