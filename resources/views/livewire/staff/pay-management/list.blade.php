<div class="agricart-categories agricart-staff-pay">
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
            <p>{{ __('agricart.staff.pay_management.list.empty_roster') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('user_number', $visibleColumns, true))
                            <th>{{ __('agricart.staff.pay_management.list.col_user_number') }}</th>
                        @endif
                        @if (in_array('name', $visibleColumns, true))
                            <th>{{ __('agricart.staff.pay_management.list.col_name') }}</th>
                        @endif
                        @if (in_array('monthly_gross', $visibleColumns, true))
                            <th>{{ __('agricart.staff.pay_management.list.col_monthly_gross') }}</th>
                        @endif
                        @if (in_array('effective_from', $visibleColumns, true))
                            <th>{{ __('agricart.staff.pay_management.list.col_effective_from') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.staff.pay_management.list.col_status') }}</th>
                        @endif
                        <th>{{ __('agricart.staff.pay_management.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $member)
                        @php
                            $salary = $salaryByUser->get($member->id);
                        @endphp
                        <tr wire:key="pay-member-{{ $member->id }}" class="agricart-categories__row">
                            @if (in_array('user_number', $visibleColumns, true))
                                <td><code>{{ $member->user_number ?: '—' }}</code></td>
                            @endif
                            @if (in_array('name', $visibleColumns, true))
                                <td>{{ $member->name }}</td>
                            @endif
                            @if (in_array('monthly_gross', $visibleColumns, true))
                                <td>
                                    @if ($salary)
                                        {{ number_format((float) $salary->monthly_gross, 0) }} {{ $salary->currency }}
                                    @else
                                        <span class="agricart-staff-pay__muted">{{ __('agricart.staff.pay_management.list.not_set') }}</span>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('effective_from', $visibleColumns, true))
                                <td>
                                    @if ($salary)
                                        {{ $salary->effective_from->format('d M Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>
                                    @if ($salary)
                                        <span @class([
                                            'agricart-staff-members__status',
                                            'is-active' => $salary->isCurrent(),
                                            'is-inactive' => ! $salary->isCurrent(),
                                        ])>
                                            {{ $salary->isCurrent()
                                                ? __('agricart.staff.pay_management.statuses.current')
                                                : __('agricart.staff.pay_management.statuses.scheduled') }}
                                        </span>
                                    @else
                                        <span class="agricart-staff-pay__muted">{{ __('agricart.staff.pay_management.statuses.unassigned') }}</span>
                                    @endif
                                </td>
                            @endif
                            <td>
                                <div class="agricart-categories__actions">
                                    @if ($canManage)
                                        @if ($salary)
                                            <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $member->id }})">
                                                {{ __('agricart.staff.pay_management.actions.edit_short') }}
                                            </button>
                                        @else
                                            <button type="button" class="agricart-categories__action" wire:click="openAssign({{ $member->id }})">
                                                {{ __('agricart.staff.pay_management.actions.assign_short') }}
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $members->links() }}
        </div>
    @endif
</div>
