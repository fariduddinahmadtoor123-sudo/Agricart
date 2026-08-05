<div class="agricart-categories agricart-contact-message-templates">
    @if (filled($statusMessage))
        <div @class([
            'agricart-portable-import__alert',
            'is-success' => $statusTone === 'success',
            'is-danger' => $statusTone === 'danger',
        ]) role="status">
            <p>{{ $statusMessage }}</p>
        </div>
    @endif

    <p class="agricart-portable-import__hint" style="margin-bottom: 1rem;">
        {{ __('contact_message_templates.hint') }}
    </p>

    @if (! $canView)
        <div class="agricart-categories__empty">
            <p>{{ __('contact_message_templates.forbidden') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        <th>{{ __('contact_message_templates.fields.name') }}</th>
                        <th>{{ __('contact_message_templates.fields.subject_en') }}</th>
                        <th>{{ __('contact_message_templates.fields.subject_ur') }}</th>
                        <th>{{ __('contact_message_templates.fields.status') }}</th>
                        <th>{{ __('contact_message_templates.fields.order') }}</th>
                        @if ($canEdit)
                            <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($templates as $template)
                        <tr wire:key="cmt-{{ $template->id }}">
                            <td>{{ $template->localizedName() }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) $template->subject_en, 48) }}</td>
                            <td dir="rtl">{{ \Illuminate\Support\Str::limit((string) ($template->subject_ur ?: '—'), 48) }}</td>
                            <td>
                                {{ $template->is_active
                                    ? __('contact_message_templates.status.active')
                                    : __('contact_message_templates.status.inactive') }}
                            </td>
                            <td class="agricart-categories__actions">
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="move({{ $template->id }}, 'up')" @disabled($loop->first)>
                                        {{ __('contact_message_templates.actions.up') }}
                                    </button>
                                    <button type="button" class="agricart-categories__action" wire:click="move({{ $template->id }}, 'down')" @disabled($loop->last)>
                                        {{ __('contact_message_templates.actions.down') }}
                                    </button>
                                @else
                                    {{ $template->sort_order }}
                                @endif
                            </td>
                            @if ($canEdit)
                                <td class="agricart-categories__actions">
                                    <button type="button" class="agricart-categories__action" wire:click="edit({{ $template->id }})">
                                        {{ __('contact_message_templates.actions.edit') }}
                                    </button>
                                    <button type="button" class="agricart-categories__action" wire:click="toggle({{ $template->id }})">
                                        {{ __('contact_message_templates.actions.toggle') }}
                                    </button>
                                    @if ($template->key !== 'welcome')
                                        <button type="button" class="agricart-categories__action is-danger" wire:click="delete({{ $template->id }})" wire:confirm="{{ __('contact_message_templates.confirm_delete') }}">
                                            {{ __('contact_message_templates.actions.delete') }}
                                        </button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canEdit ? 6 : 5 }}" class="agricart-categories__empty-cell">
                                {{ __('contact_message_templates.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($canEdit && ! $showForm)
            <div class="agricart-portable-import__actions" style="margin-top: 1rem;">
                <button type="button" class="agricart-categories__action is-primary" wire:click="openCreate">
                    {{ __('contact_message_templates.create') }}
                </button>
            </div>
        @endif
    @endif

    @if ($canEdit && $showForm)
        <div
            id="contact-message-template-form"
            class="agricart-portable-import agricart-customer-message-panel"
            style="margin-top: 1.25rem;"
            wire:key="template-form-{{ $editingId ?? 'new' }}"
        >
            <div class="agricart-customer-message-panel__card">
                <h3 class="agricart-portable-import__title">
                    {{ $editingId ? __('contact_message_templates.edit') : __('contact_message_templates.create') }}
                </h3>
                <p class="agricart-portable-import__hint">{{ __('contact_message_templates.placeholder_hint') }}</p>

                <div class="agricart-customer-message-panel__field">
                    <label class="agricart-form-panel__label" for="cmt-name-en">{{ __('contact_message_templates.fields.name_en') }}</label>
                    <input id="cmt-name-en" type="text" class="agricart-customer-message-panel__input" wire:model="name_en" maxlength="120" />
                    @error('name_en') <p class="agricart-form-panel__hint is-danger">{{ $message }}</p> @enderror
                </div>

                <div class="agricart-customer-message-panel__field">
                    <label class="agricart-form-panel__label" for="cmt-name-ur">{{ __('contact_message_templates.fields.name_ur') }}</label>
                    <input id="cmt-name-ur" type="text" class="agricart-customer-message-panel__input" wire:model="name_ur" maxlength="120" dir="rtl" />
                    @error('name_ur') <p class="agricart-form-panel__hint is-danger">{{ $message }}</p> @enderror
                </div>

                <div class="agricart-customer-message-panel__field">
                    <label class="agricart-form-panel__label" for="cmt-subject-en">{{ __('contact_message_templates.fields.subject_en') }}</label>
                    <input id="cmt-subject-en" type="text" class="agricart-customer-message-panel__input" wire:model="subject_en" maxlength="200" />
                    @error('subject_en') <p class="agricart-form-panel__hint is-danger">{{ $message }}</p> @enderror
                </div>

                <div class="agricart-customer-message-panel__field">
                    <label class="agricart-form-panel__label" for="cmt-subject-ur">{{ __('contact_message_templates.fields.subject_ur') }}</label>
                    <input id="cmt-subject-ur" type="text" class="agricart-customer-message-panel__input" wire:model="subject_ur" maxlength="200" dir="rtl" />
                    @error('subject_ur') <p class="agricart-form-panel__hint is-danger">{{ $message }}</p> @enderror
                </div>

                <div class="agricart-customer-message-panel__field">
                    <label class="agricart-form-panel__label" for="cmt-body-en">{{ __('contact_message_templates.fields.body_en') }}</label>
                    <textarea id="cmt-body-en" class="agricart-customer-message-panel__textarea" rows="12" cols="120" wire:model="body_en" maxlength="5000"></textarea>
                    @error('body_en') <p class="agricart-form-panel__hint is-danger">{{ $message }}</p> @enderror
                </div>

                <div class="agricart-customer-message-panel__field">
                    <label class="agricart-form-panel__label" for="cmt-body-ur">{{ __('contact_message_templates.fields.body_ur') }}</label>
                    <textarea id="cmt-body-ur" class="agricart-customer-message-panel__textarea" rows="12" cols="120" wire:model="body_ur" maxlength="5000" dir="rtl"></textarea>
                    @error('body_ur') <p class="agricart-form-panel__hint is-danger">{{ $message }}</p> @enderror
                </div>

                <label class="agricart-form-panel__label" style="display:flex;gap:0.5rem;align-items:center;margin-top:0.75rem;">
                    <input type="checkbox" wire:model="is_active" />
                    <span>{{ __('contact_message_templates.fields.active') }}</span>
                </label>

                <div class="agricart-portable-import__actions agricart-customer-message-panel__actions">
                    <button type="button" class="agricart-categories__action is-primary" wire:click="save" wire:loading.attr="disabled">
                        {{ $editingId ? __('contact_message_templates.actions.update') : __('contact_message_templates.actions.create') }}
                    </button>
                    <button type="button" class="agricart-categories__action" wire:click="closeForm">
                        {{ __('contact_message_templates.actions.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
