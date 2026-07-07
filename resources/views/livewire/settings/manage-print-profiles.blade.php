<div class="agricart-printing">
    @if ($flashMessage)
        <div @class([
            'agricart-backups__alert',
            'is-success' => $flashType === 'success',
            'is-danger' => $flashType === 'danger',
        ])>
            {{ $flashMessage }}
        </div>
    @endif

    <section class="agricart-backups__panel">
        <header class="agricart-backups__panel-head">
            <h2>{{ __('agricart.printing.sections.profiles') }}</h2>
            <p>{{ __('agricart.printing.sections.profiles_hint') }}</p>
        </header>

        @if ($canManage)
            <div class="agricart-backups__actions">
                <button type="button" class="agricart-backups__btn is-primary" wire:click="openCreate">
                    {{ __('agricart.printing.actions.add_profile') }}
                </button>
            </div>
        @endif

        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table agricart-printing__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.printing.list.col_name') }}</th>
                        <th>{{ __('agricart.printing.list.col_category') }}</th>
                        <th>{{ __('agricart.printing.list.col_size') }}</th>
                        <th>{{ __('agricart.printing.list.col_margin') }}</th>
                        <th>{{ __('agricart.printing.list.col_printer') }}</th>
                        <th>{{ __('agricart.printing.list.col_default') }}</th>
                        <th>{{ __('agricart.printing.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($profiles as $profile)
                        <tr wire:key="print-profile-{{ $profile->id }}">
                            @if ($editingId === $profile->id)
                                <td colspan="7" class="agricart-printing__edit-row">
                                    <div class="agricart-printing__edit-grid">
                                        <label>
                                            <span>{{ __('agricart.printing.fields.name_en') }}</span>
                                            <input type="text" wire:model="editForm.name_en" class="agricart-backups__input" />
                                        </label>
                                        <label>
                                            <span>{{ __('agricart.printing.fields.name_ur') }}</span>
                                            <input type="text" wire:model="editForm.name_ur" class="agricart-backups__input" />
                                        </label>
                                        <label>
                                            <span>{{ __('agricart.printing.fields.width_mm') }}</span>
                                            <input type="number" step="0.1" wire:model="editForm.width_mm" class="agricart-backups__input" />
                                        </label>
                                        <label>
                                            <span>{{ __('agricart.printing.fields.height_mm') }}</span>
                                            <input type="number" step="0.1" wire:model="editForm.height_mm" class="agricart-backups__input" @if($profile->category === 'receipt') placeholder="{{ __('agricart.printing.fields.roll_auto') }}" @endif />
                                        </label>
                                        <label>
                                            <span>{{ __('agricart.printing.fields.margin_mm') }}</span>
                                            <input type="number" step="0.1" wire:model="editForm.margin_mm" class="agricart-backups__input" />
                                        </label>
                                        <label>
                                            <span>{{ __('agricart.printing.fields.orientation') }}</span>
                                            <select wire:model="editForm.orientation" class="agricart-backups__input">
                                                <option value="portrait">{{ __('agricart.printing.orientation.portrait') }}</option>
                                                <option value="landscape">{{ __('agricart.printing.orientation.landscape') }}</option>
                                            </select>
                                        </label>
                                        <label class="agricart-printing__edit-wide">
                                            <span>{{ __('agricart.printing.fields.printer_hint') }}</span>
                                            <input type="text" wire:model="editForm.printer_hint" class="agricart-backups__input" />
                                        </label>
                                    </div>
                                    <div class="agricart-printing__edit-actions">
                                        <button type="button" class="agricart-categories__action" wire:click="cancelEdit">{{ __('agricart.printing.actions.cancel') }}</button>
                                        <button type="button" class="agricart-categories__action is-primary" wire:click="saveEdit">{{ __('agricart.printing.actions.save') }}</button>
                                    </div>
                                </td>
                            @else
                                <td>
                                    <strong>{{ $profile->localizedName() }}</strong>
                                    @if ($profile->is_system)
                                        <span class="agricart-printing__badge">{{ __('agricart.printing.badges.system') }}</span>
                                    @endif
                                </td>
                                <td>{{ __('agricart.printing.categories.' . $profile->category) }}@if($profile->label_kind) · {{ __('agricart.printing.label_kinds.' . $profile->label_kind) }}@endif</td>
                                <td>{{ $profile->dimensionsLabel() }}</td>
                                <td>{{ rtrim(rtrim(number_format($profile->margin_mm, 1, '.', ''), '0'), '.') }} mm</td>
                                <td>{{ $profile->printer_hint ?: '—' }}</td>
                                <td>
                                    @if ($profile->is_default)
                                        <span class="agricart-printing__badge is-default">{{ __('agricart.printing.badges.default') }}</span>
                                    @elseif ($canManage)
                                        <button type="button" class="agricart-categories__action" wire:click="setDefault(@js($profile->id))">
                                            {{ __('agricart.printing.actions.make_default') }}
                                        </button>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($canManage)
                                        <div class="agricart-categories__actions">
                                            <button type="button" class="agricart-categories__action" wire:click="startEdit(@js($profile->id))">
                                                {{ __('agricart.printing.actions.edit') }}
                                            </button>
                                            @if (! $profile->is_system)
                                                <button
                                                    type="button"
                                                    class="agricart-categories__action is-danger"
                                                    wire:click="delete(@js($profile->id))"
                                                    wire:confirm="{{ __('agricart.printing.messages.delete_confirm') }}"
                                                >
                                                    {{ __('agricart.printing.actions.delete') }}
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="agricart-backups__panel">
        <header class="agricart-backups__panel-head">
            <h2>{{ __('agricart.printing.sections.how_it_works') }}</h2>
            <p>{{ __('agricart.printing.sections.how_it_works_hint') }}</p>
        </header>
        <ul class="agricart-backups__limits">
            <li>{{ __('agricart.printing.notes.browser') }}</li>
            <li>{{ __('agricart.printing.notes.labels_ready') }}</li>
            <li>{{ __('agricart.printing.notes.invoices_later') }}</li>
        </ul>
    </section>

    @if ($showCreate)
        <div class="agricart-staff-approvals__modal" wire:keydown.escape.window="cancelCreate">
            <div class="agricart-staff-approvals__modal-backdrop" wire:click="cancelCreate"></div>
            <div class="agricart-staff-approvals__modal-card agricart-staff-approvals__modal-card--wide" role="dialog" aria-modal="true">
                <h3 class="agricart-staff-approvals__modal-title">{{ __('agricart.printing.actions.add_profile') }}</h3>

                <div class="agricart-printing__edit-grid">
                    <label>
                        <span>{{ __('agricart.printing.fields.name_en') }}</span>
                        <input type="text" wire:model="createForm.name_en" class="agricart-backups__input" />
                    </label>
                    <label>
                        <span>{{ __('agricart.printing.fields.name_ur') }}</span>
                        <input type="text" wire:model="createForm.name_ur" class="agricart-backups__input" />
                    </label>
                    <label>
                        <span>{{ __('agricart.printing.fields.category') }}</span>
                        <select wire:model.live="createForm.category" class="agricart-backups__input">
                            <option value="document">{{ __('agricart.printing.categories.document') }}</option>
                            <option value="receipt">{{ __('agricart.printing.categories.receipt') }}</option>
                            <option value="label">{{ __('agricart.printing.categories.label') }}</option>
                        </select>
                    </label>
                    @if ($createForm['category'] === 'label')
                        <label>
                            <span>{{ __('agricart.printing.fields.label_kind') }}</span>
                            <select wire:model="createForm.label_kind" class="agricart-backups__input">
                                <option value="product">{{ __('agricart.printing.label_kinds.product') }}</option>
                                <option value="price">{{ __('agricart.printing.label_kinds.price') }}</option>
                            </select>
                        </label>
                    @endif
                    <label>
                        <span>{{ __('agricart.printing.fields.width_mm') }}</span>
                        <input type="number" step="0.1" wire:model="createForm.width_mm" class="agricart-backups__input" />
                    </label>
                    <label>
                        <span>{{ __('agricart.printing.fields.height_mm') }}</span>
                        <input type="number" step="0.1" wire:model="createForm.height_mm" class="agricart-backups__input" @if($createForm['category'] === 'receipt') placeholder="{{ __('agricart.printing.fields.roll_auto') }}" @endif />
                    </label>
                    <label>
                        <span>{{ __('agricart.printing.fields.margin_mm') }}</span>
                        <input type="number" step="0.1" wire:model="createForm.margin_mm" class="agricart-backups__input" />
                    </label>
                    <label>
                        <span>{{ __('agricart.printing.fields.orientation') }}</span>
                        <select wire:model="createForm.orientation" class="agricart-backups__input">
                            <option value="portrait">{{ __('agricart.printing.orientation.portrait') }}</option>
                            <option value="landscape">{{ __('agricart.printing.orientation.landscape') }}</option>
                        </select>
                    </label>
                    <label class="agricart-printing__edit-wide">
                        <span>{{ __('agricart.printing.fields.printer_hint') }}</span>
                        <input type="text" wire:model="createForm.printer_hint" class="agricart-backups__input" />
                    </label>
                </div>

                <div class="agricart-staff-approvals__modal-actions">
                    <button type="button" class="agricart-categories__action" wire:click="cancelCreate">{{ __('agricart.printing.actions.cancel') }}</button>
                    <button type="button" class="agricart-categories__action is-primary" wire:click="createProfile">{{ __('agricart.printing.actions.save') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
