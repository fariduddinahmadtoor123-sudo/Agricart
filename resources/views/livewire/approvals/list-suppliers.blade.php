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

    @if ($suppliers->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.approvals.suppliers.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.approvals.suppliers.list.col_code') }}</th>
                        <th>{{ __('agricart.approvals.suppliers.list.col_business') }}</th>
                        <th>{{ __('agricart.approvals.suppliers.list.col_type') }}</th>
                        <th>{{ __('agricart.approvals.suppliers.list.col_city') }}</th>
                        <th>{{ __('agricart.approvals.suppliers.list.col_contact') }}</th>
                        <th>{{ __('agricart.approvals.suppliers.list.col_status') }}</th>
                        <th>{{ __('agricart.approvals.suppliers.list.col_created') }}</th>
                        <th>{{ __('agricart.approvals.suppliers.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($suppliers as $supplier)
                        <tr wire:key="supplier-approval-{{ $supplier->id }}" class="agricart-categories__row">
                            <td><code>{{ $supplier->supplier_code ?: '—' }}</code></td>
                            <td>{{ $supplier->displayBusinessName() }}</td>
                            <td>{{ $supplier->supplierTypeLabel() }}</td>
                            <td>{{ $supplier->displayCity() ?: '—' }}</td>
                            <td>
                                {{ $supplier->primaryContactName() }}
                                @if ($supplier->primaryMobile())
                                    <br><small>{{ $supplier->primaryMobile() }}</small>
                                @endif
                            </td>
                            <td>{{ __('suppliers.statuses.' . ($supplier->status ?? 'pending')) }}</td>
                            <td>{{ $supplier->created_at?->format('Y-m-d') }}</td>
                            <td>
                                @if ($canManage)
                                    <button type="button" class="agricart-categories__action" wire:click="openView({{ $supplier->id }})">
                                        {{ __('agricart.approvals.suppliers.actions.view') }}
                                    </button>
                                    @if ($supplier->isPending())
                                        <button type="button" class="agricart-categories__action" wire:click="openApprove({{ $supplier->id }})">
                                            {{ __('agricart.approvals.suppliers.actions.approve') }}
                                        </button>
                                        <button type="button" class="agricart-categories__action is-danger" wire:click="openReject({{ $supplier->id }})">
                                            {{ __('agricart.approvals.suppliers.actions.reject') }}
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
            {{ $suppliers->links() }}
        </div>
    @endif

    @if ($viewSupplier)
        <div class="agricart-staff-approvals__modal" wire:keydown.escape.window="closeView">
            <div class="agricart-staff-approvals__modal-backdrop" wire:click="closeView"></div>
            <div class="agricart-staff-approvals__modal-card agricart-staff-approvals__modal-card--wide" role="dialog" aria-modal="true">
                <div class="agricart-staff-approvals__modal-header">
                    <h3 class="agricart-staff-approvals__modal-title">
                        {{ __('agricart.approvals.suppliers.actions.view_title', ['name' => $viewSupplier->displayBusinessName()]) }}
                    </h3>
                    <button type="button" class="agricart-staff-approvals__close" wire:click="closeView" aria-label="{{ __('agricart.form_panel.close') }}">&times;</button>
                </div>

                <div class="agricart-staff-approvals__detail-grid">
                    <div class="agricart-staff-approvals__detail-item">
                        <span class="agricart-staff-approvals__detail-label">{{ __('agricart.approvals.suppliers.list.col_code') }}</span>
                        <span class="agricart-staff-approvals__detail-value"><code>{{ $viewSupplier->supplier_code ?: '—' }}</code></span>
                    </div>
                    <div class="agricart-staff-approvals__detail-item">
                        <span class="agricart-staff-approvals__detail-label">{{ __('agricart.approvals.suppliers.list.col_type') }}</span>
                        <span class="agricart-staff-approvals__detail-value">{{ $viewSupplier->supplierTypeLabel() }}</span>
                    </div>
                    <div class="agricart-staff-approvals__detail-item">
                        <span class="agricart-staff-approvals__detail-label">{{ __('agricart.approvals.suppliers.list.col_city') }}</span>
                        <span class="agricart-staff-approvals__detail-value">{{ $viewSupplier->displayCity() ?: '—' }}</span>
                    </div>
                    <div class="agricart-staff-approvals__detail-item">
                        <span class="agricart-staff-approvals__detail-label">{{ __('agricart.approvals.suppliers.list.col_status') }}</span>
                        <span class="agricart-staff-approvals__detail-value">{{ __('suppliers.statuses.' . ($viewSupplier->status ?? 'pending')) }}</span>
                    </div>
                    <div class="agricart-staff-approvals__detail-item agricart-staff-approvals__detail-item--full">
                        <span class="agricart-staff-approvals__detail-label">{{ __('agricart.supplier_register.fields.address') }}</span>
                        <span class="agricart-staff-approvals__detail-value">{{ $viewSupplier->address_en ?: '—' }}</span>
                    </div>
                    <div class="agricart-staff-approvals__detail-item agricart-staff-approvals__detail-item--full">
                        <span class="agricart-staff-approvals__detail-label">{{ __('agricart.supplier_register.fields.application_note') }}</span>
                        <span class="agricart-staff-approvals__detail-value">{{ $viewSupplier->application_note ?: '—' }}</span>
                    </div>
                    <div class="agricart-staff-approvals__detail-item agricart-staff-approvals__detail-item--full">
                        <span class="agricart-staff-approvals__detail-label">{{ __('agricart.supplier_register.fields.custom_categories') }}</span>
                        <span class="agricart-staff-approvals__detail-value">{{ $viewSupplier->custom_categories ?: '—' }}</span>
                    </div>
                </div>

                @php
                    $contacts = is_array($viewSupplier->contacts) ? $viewSupplier->contacts : [];
                @endphp

                @if ($contacts !== [])
                    <section class="agricart-staff-approvals__section">
                        <h4 class="agricart-staff-approvals__section-title">{{ __('agricart.supplier_register.sections.people') }}</h4>
                        <div class="agricart-staff-approvals__table-mini-wrap">
                            <table class="agricart-staff-approvals__table-mini">
                                <thead>
                                    <tr>
                                        <th>{{ __('agricart.supplier_register.fields.contact_name') }}</th>
                                        <th>{{ __('agricart.supplier_register.fields.contact_phone') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($contacts as $contact)
                                        <tr>
                                            <td>{{ $contact['contact_name_en'] ?? '—' }}</td>
                                            <td>{{ $contact['mobile_number'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                @if ($viewSupplier->categories->isNotEmpty())
                    <section class="agricart-staff-approvals__section">
                        <h4 class="agricart-staff-approvals__section-title">{{ __('agricart.supplier_register.sections.categories') }}</h4>
                        <p>{{ $viewSupplier->categories->pluck('name_en')->filter()->implode(', ') }}</p>
                    </section>
                @endif

                @if (filled($viewSupplier->rejection_reason))
                    <section class="agricart-staff-approvals__section agricart-staff-approvals__rejection-box">
                        <h4 class="agricart-staff-approvals__section-title">{{ __('agricart.approvals.suppliers.view.rejection_reason') }}</h4>
                        <p>{{ $viewSupplier->rejection_reason }}</p>
                        @if ($viewSupplier->rejected_at)
                            <p class="agricart-staff-approvals__muted">{{ __('agricart.approvals.suppliers.view.rejected_at', ['date' => agricart_datetime($viewSupplier->rejected_at)]) }}</p>
                        @endif
                    </section>
                @endif

                <div class="agricart-staff-approvals__modal-actions">
                    <button type="button" class="agricart-categories__action" wire:click="closeView">{{ __('agricart.approvals.suppliers.actions.close') }}</button>
                    @if ($viewSupplier->isPending())
                        <button type="button" class="agricart-categories__action" wire:click="openApprove({{ $viewSupplier->id }})">{{ __('agricart.approvals.suppliers.actions.approve') }}</button>
                        <button type="button" class="agricart-categories__action is-danger" wire:click="openReject({{ $viewSupplier->id }})">{{ __('agricart.approvals.suppliers.actions.reject') }}</button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if ($approveSupplierId)
        <div class="agricart-staff-approvals__modal" wire:keydown.escape.window="cancelApprove">
            <div class="agricart-staff-approvals__modal-backdrop" wire:click="cancelApprove"></div>
            <div class="agricart-staff-approvals__modal-card" role="dialog" aria-modal="true">
                <h3 class="agricart-staff-approvals__modal-title">{{ __('agricart.approvals.suppliers.actions.approve_title') }}</h3>
                <p class="agricart-staff-approvals__modal-text">{{ __('agricart.approvals.suppliers.actions.approve_hint') }}</p>
                <div class="agricart-staff-approvals__modal-actions">
                    <button type="button" class="agricart-categories__action" wire:click="cancelApprove">{{ __('agricart.approvals.suppliers.actions.cancel') }}</button>
                    <button type="button" class="agricart-categories__action" wire:click="confirmApprove">{{ __('agricart.approvals.suppliers.actions.confirm_approve') }}</button>
                </div>
            </div>
        </div>
    @endif

    @if ($rejectSupplierId)
        <div class="agricart-staff-approvals__modal" wire:keydown.escape.window="cancelReject">
            <div class="agricart-staff-approvals__modal-backdrop" wire:click="cancelReject"></div>
            <div class="agricart-staff-approvals__modal-card agricart-staff-approvals__modal-card--wide" role="dialog" aria-modal="true">
                <h3 class="agricart-staff-approvals__modal-title">{{ __('agricart.approvals.suppliers.actions.reject_title') }}</h3>
                <p class="agricart-staff-approvals__modal-text">{{ __('agricart.approvals.suppliers.actions.reject_hint') }}</p>
                <label class="agricart-form-panel__label" for="supplier-reject-reason">{{ __('agricart.approvals.suppliers.fields.rejection_reason') }}</label>
                <textarea
                    id="supplier-reject-reason"
                    wire:model="rejectReason"
                    class="agricart-staff-approvals__textarea"
                    rows="4"
                    placeholder="{{ __('agricart.approvals.suppliers.fields.rejection_reason_placeholder') }}"
                ></textarea>
                <div class="agricart-staff-approvals__modal-actions">
                    <button type="button" class="agricart-categories__action" wire:click="cancelReject">{{ __('agricart.approvals.suppliers.actions.cancel') }}</button>
                    <button type="button" class="agricart-categories__action is-danger" wire:click="confirmReject">{{ __('agricart.approvals.suppliers.actions.confirm_reject') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
