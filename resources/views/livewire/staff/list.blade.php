<div class="agricart-categories agricart-staff-members">
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
            <p>{{ __('agricart.staff.members.list.empty') }}</p>
            @if ($canCreate)
                <p class="agricart-staff-members__empty-hint">{{ __('agricart.staff.members.list.empty_hint') }}</p>
            @endif
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
                        @if (in_array('email', $visibleColumns, true))
                            <th>{{ __('agricart.staff.members.list.col_email') }}</th>
                        @endif
                        @if (in_array('role', $visibleColumns, true))
                            <th>{{ __('agricart.staff.members.list.col_role') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.staff.members.list.col_status') }}</th>
                        @endif
                        <th>{{ __('agricart.staff.members.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $member)
                        <tr wire:key="staff-member-{{ $member->id }}" class="agricart-categories__row">
                            @if (in_array('user_number', $visibleColumns, true))
                                <td><code>{{ $member->user_number ?: '—' }}</code></td>
                            @endif
                            @if (in_array('name', $visibleColumns, true))
                                <td>{{ $member->name }}</td>
                            @endif
                            @if (in_array('email', $visibleColumns, true))
                                <td>{{ $member->email }}</td>
                            @endif
                            @if (in_array('role', $visibleColumns, true))
                                <td>{{ $member->role?->name ?? '—' }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>
                                    <span @class([
                                        'agricart-staff-members__status',
                                        'is-active' => $member->status === \App\Models\User::STATUS_ACTIVE,
                                        'is-inactive' => $member->status === \App\Models\User::STATUS_INACTIVE,
                                    ])>
                                        {{ __('agricart.users.statuses.' . ($member->status ?? 'inactive')) }}
                                    </span>
                                </td>
                            @endif
                            <td>
                                <div class="agricart-categories__actions">
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $member->id }})">
                                        {{ __('agricart.staff.members.actions.edit_short') }}
                                    </button>
                                    @if ($canEdit && auth()->id() !== $member->id && ! ($member->isSuperAdmin() && ! auth()->user()?->isSuperAdmin()))
                                        @if ($member->status === \App\Models\User::STATUS_ACTIVE)
                                            <button type="button" class="agricart-categories__action agricart-staff-members__action-muted" wire:click="deactivate({{ $member->id }})">
                                                {{ __('agricart.staff.members.actions.deactivate') }}
                                            </button>
                                        @else
                                            <button type="button" class="agricart-categories__action" wire:click="activate({{ $member->id }})">
                                                {{ __('agricart.staff.members.actions.activate') }}
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
