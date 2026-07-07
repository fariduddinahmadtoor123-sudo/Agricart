<div class="agricart-categories agricart-staff-approvals">
    @if ($flashMessage)
        <div @class([
            'agricart-user-form__alert',
            'is-success' => $flashType === 'success',
            'is-danger' => $flashType === 'danger',
        ])>
            {{ $flashMessage }}
        </div>
    @endif

    @if ($users->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.approvals.staff.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.users.list.col_user_number') }}</th>
                        <th>{{ __('agricart.users.list.col_name') }}</th>
                        <th>{{ __('agricart.users.list.col_email') }}</th>
                        <th>{{ __('agricart.users.list.col_role') }}</th>
                        <th>{{ __('agricart.users.list.col_status') }}</th>
                        <th>{{ __('agricart.users.list.col_created') }}</th>
                        <th>{{ __('agricart.approvals.staff.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr wire:key="staff-approval-{{ $user->id }}" class="agricart-categories__row">
                            <td><code>{{ $user->user_number ?: '—' }}</code></td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role?->name ?? '—' }}</td>
                            <td>{{ __('agricart.users.statuses.' . ($user->status ?? 'pending')) }}</td>
                            <td>{{ $user->created_at?->format('Y-m-d') }}</td>
                            <td>
                                @if ($canManage && $user->isPending())
                                    <button type="button" class="agricart-categories__action" wire:click="openApprove({{ $user->id }})">
                                        {{ __('agricart.approvals.staff.actions.approve') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="agricart-categories__action is-danger"
                                        wire:click="reject({{ $user->id }})"
                                        wire:confirm="{{ __('agricart.approvals.staff.messages.reject_confirm', ['name' => $user->name]) }}"
                                    >
                                        {{ __('agricart.approvals.staff.actions.reject') }}
                                    </button>
                                @else
                                    <span class="agricart-staff-approvals__muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $users->links() }}
        </div>
    @endif

    @if ($approveUserId)
        <div class="agricart-staff-approvals__modal" wire:keydown.escape.window="cancelApprove">
            <div class="agricart-staff-approvals__modal-backdrop" wire:click="cancelApprove"></div>
            <div class="agricart-staff-approvals__modal-card" role="dialog" aria-modal="true">
                <h3 class="agricart-staff-approvals__modal-title">{{ __('agricart.approvals.staff.actions.approve_title') }}</h3>
                <p class="agricart-staff-approvals__modal-text">{{ __('agricart.approvals.staff.actions.approve_hint') }}</p>

                <label class="agricart-form-panel__label" for="staff-approve-role">{{ __('agricart.users.fields.role') }}</label>
                <select id="staff-approve-role" wire:model="approveRoleId" class="agricart-staff-approvals__select">
                    <option value="">{{ __('agricart.users.hints.select_role') }}</option>
                    @foreach ($roleOptions as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>

                <div class="agricart-staff-approvals__modal-actions">
                    <button type="button" class="agricart-categories__action" wire:click="cancelApprove">{{ __('agricart.approvals.staff.actions.cancel') }}</button>
                    <button type="button" class="agricart-categories__action" wire:click="confirmApprove">{{ __('agricart.approvals.staff.actions.confirm_approve') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
