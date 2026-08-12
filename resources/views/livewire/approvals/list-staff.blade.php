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
                                @if ($canManage)
                                    <button type="button" class="agricart-categories__action" wire:click="openView({{ $user->id }})">
                                        {{ __('agricart.approvals.staff.actions.view') }}
                                    </button>
                                    @if ($user->isPending())
                                        <button type="button" class="agricart-categories__action" wire:click="openApprove({{ $user->id }})">
                                            {{ __('agricart.approvals.staff.actions.approve') }}
                                        </button>
                                        <button type="button" class="agricart-categories__action is-danger" wire:click="openReject({{ $user->id }})">
                                            {{ __('agricart.approvals.staff.actions.reject') }}
                                        </button>
                                    @else
                                        <span class="agricart-staff-approvals__muted">—</span>
                                    @endif
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

    @if ($viewUser)
        <div class="agricart-staff-approvals__modal" wire:keydown.escape.window="closeView">
            <div class="agricart-staff-approvals__modal-backdrop" wire:click="closeView"></div>
            <div class="agricart-staff-approvals__modal-card agricart-staff-approvals__modal-card--wide" role="dialog" aria-modal="true">
                <div class="agricart-staff-approvals__modal-header">
                    <h3 class="agricart-staff-approvals__modal-title">{{ __('agricart.approvals.staff.actions.view_title', ['name' => $viewUser->name]) }}</h3>
                    <button type="button" class="agricart-staff-approvals__close" wire:click="closeView" aria-label="{{ __('agricart.form_panel.close') }}">&times;</button>
                </div>

                <div class="agricart-staff-approvals__detail-grid">
                    <div class="agricart-staff-approvals__detail-item">
                        <span class="agricart-staff-approvals__detail-label">{{ __('agricart.users.fields.user_number') }}</span>
                        <span class="agricart-staff-approvals__detail-value"><code>{{ $viewUser->user_number ?: '—' }}</code></span>
                    </div>
                    <div class="agricart-staff-approvals__detail-item">
                        <span class="agricart-staff-approvals__detail-label">{{ __('agricart.users.fields.name') }}</span>
                        <span class="agricart-staff-approvals__detail-value">{{ $viewUser->name }}</span>
                    </div>
                    <div class="agricart-staff-approvals__detail-item">
                        <span class="agricart-staff-approvals__detail-label">{{ __('agricart.users.fields.email') }}</span>
                        <span class="agricart-staff-approvals__detail-value">{{ $viewUser->email }}</span>
                    </div>
                    <div class="agricart-staff-approvals__detail-item">
                        <span class="agricart-staff-approvals__detail-label">{{ __('agricart.users.fields.role') }}</span>
                        <span class="agricart-staff-approvals__detail-value">{{ $viewUser->role?->name ?? '—' }}</span>
                    </div>
                    <div class="agricart-staff-approvals__detail-item">
                        <span class="agricart-staff-approvals__detail-label">{{ __('agricart.form_panel.fields.status') }}</span>
                        <span class="agricart-staff-approvals__detail-value">{{ __('agricart.users.statuses.' . ($viewUser->status ?? 'pending')) }}</span>
                    </div>
                    <div class="agricart-staff-approvals__detail-item">
                        <span class="agricart-staff-approvals__detail-label">{{ __('agricart.users.list.col_created') }}</span>
                        <span class="agricart-staff-approvals__detail-value">{{ agricart_datetime($viewUser->created_at) }}</span>
                    </div>
                    <div class="agricart-staff-approvals__detail-item agricart-staff-approvals__detail-item--full">
                        <span class="agricart-staff-approvals__detail-label">{{ __('agricart.users.fields.address') }}</span>
                        <span class="agricart-staff-approvals__detail-value">{{ $viewUser->address ?: '—' }}</span>
                    </div>
                </div>

                @php
                    $phones = is_array($viewUser->phone_numbers) ? $viewUser->phone_numbers : [];
                    $banks = is_array($viewUser->bank_accounts) ? $viewUser->bank_accounts : [];
                @endphp

                @if ($phones !== [])
                    <section class="agricart-staff-approvals__section">
                        <h4 class="agricart-staff-approvals__section-title">{{ __('agricart.users.sections.phones') }}</h4>
                        <div class="agricart-staff-approvals__table-mini-wrap">
                            <table class="agricart-staff-approvals__table-mini">
                                <thead>
                                    <tr>
                                        <th>{{ __('agricart.users.fields.contact_person') }}</th>
                                        <th>{{ __('agricart.users.fields.phone_number') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($phones as $phone)
                                        <tr>
                                            <td>{{ $phone['contact_person'] ?? '—' }}</td>
                                            <td>{{ $phone['phone_number'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                @if ($banks !== [])
                    <section class="agricart-staff-approvals__section">
                        <h4 class="agricart-staff-approvals__section-title">{{ __('agricart.users.sections.bank_accounts') }}</h4>
                        <div class="agricart-staff-approvals__table-mini-wrap">
                            <table class="agricart-staff-approvals__table-mini">
                                <thead>
                                    <tr>
                                        <th>{{ __('agricart.users.fields.bank_name') }}</th>
                                        <th>{{ __('agricart.users.fields.account_title') }}</th>
                                        <th>{{ __('agricart.users.fields.account_number') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($banks as $bank)
                                        <tr>
                                            <td>{{ $bank['bank_name'] ?? '—' }}</td>
                                            <td>{{ $bank['account_title'] ?? '—' }}</td>
                                            <td>{{ $bank['account_number'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                <section class="agricart-staff-approvals__section">
                    <h4 class="agricart-staff-approvals__section-title">{{ __('agricart.users.sections.documents') }}</h4>
                    <div class="agricart-staff-approvals__documents">
                        @foreach ([
                            'profile_photo_path' => __('agricart.users.fields.profile_photo'),
                            'cnic_front_path' => __('agricart.users.fields.cnic_front'),
                            'cnic_back_path' => __('agricart.users.fields.cnic_back'),
                        ] as $field => $label)
                            @php
                                $side = match ($field) {
                                    'cnic_front_path' => 'front',
                                    'cnic_back_path' => 'back',
                                    default => null,
                                };
                                $url = filled($viewUser->{$field})
                                    ? ($side
                                        ? route('admin.users.documents.show', ['user' => $viewUser, 'side' => $side])
                                        : $viewUser->publicFileUrl($viewUser->{$field}))
                                    : null;
                            @endphp
                            <figure class="agricart-staff-approvals__document">
                                <figcaption>{{ $label }}</figcaption>
                                @if ($url)
                                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                                        <img src="{{ $url }}" alt="{{ $label }}" loading="lazy" />
                                    </a>
                                @else
                                    <span class="agricart-staff-approvals__muted">{{ __('agricart.approvals.staff.view.no_document') }}</span>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                </section>

                @if (filled($viewUser->rejection_reason))
                    <section class="agricart-staff-approvals__section agricart-staff-approvals__rejection-box">
                        <h4 class="agricart-staff-approvals__section-title">{{ __('agricart.approvals.staff.view.rejection_reason') }}</h4>
                        <p>{{ $viewUser->rejection_reason }}</p>
                        @if ($viewUser->rejected_at)
                            <p class="agricart-staff-approvals__muted">{{ __('agricart.approvals.staff.view.rejected_at', ['date' => agricart_datetime($viewUser->rejected_at)]) }}</p>
                        @endif
                    </section>
                @endif

                <div class="agricart-staff-approvals__modal-actions">
                    <button type="button" class="agricart-categories__action" wire:click="closeView">{{ __('agricart.approvals.staff.actions.close') }}</button>
                    @if ($viewUser->isPending())
                        <button type="button" class="agricart-categories__action" wire:click="openApprove({{ $viewUser->id }})">{{ __('agricart.approvals.staff.actions.approve') }}</button>
                        <button type="button" class="agricart-categories__action is-danger" wire:click="openReject({{ $viewUser->id }})">{{ __('agricart.approvals.staff.actions.reject') }}</button>
                    @endif
                </div>
            </div>
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

    @if ($rejectUserId)
        <div class="agricart-staff-approvals__modal" wire:keydown.escape.window="cancelReject">
            <div class="agricart-staff-approvals__modal-backdrop" wire:click="cancelReject"></div>
            <div class="agricart-staff-approvals__modal-card agricart-staff-approvals__modal-card--wide" role="dialog" aria-modal="true">
                <h3 class="agricart-staff-approvals__modal-title">{{ __('agricart.approvals.staff.actions.reject_title') }}</h3>
                <p class="agricart-staff-approvals__modal-text">{{ __('agricart.approvals.staff.actions.reject_hint') }}</p>

                <label class="agricart-form-panel__label" for="staff-reject-reason">{{ __('agricart.approvals.staff.fields.rejection_reason') }}</label>
                <textarea
                    id="staff-reject-reason"
                    wire:model="rejectReason"
                    class="agricart-staff-approvals__textarea"
                    rows="4"
                    placeholder="{{ __('agricart.approvals.staff.fields.rejection_reason_placeholder') }}"
                ></textarea>

                <div class="agricart-staff-approvals__modal-actions">
                    <button type="button" class="agricart-categories__action" wire:click="cancelReject">{{ __('agricart.approvals.staff.actions.cancel') }}</button>
                    <button type="button" class="agricart-categories__action is-danger" wire:click="confirmReject">{{ __('agricart.approvals.staff.actions.confirm_reject') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
