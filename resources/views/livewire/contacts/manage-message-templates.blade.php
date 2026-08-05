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
                        <th></th>
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
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $template->id }})">
                                    {{ __('contact_message_templates.actions.view') }}
                                </button>
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $template->id }})">
                                        {{ __('contact_message_templates.actions.edit') }}
                                    </button>
                                    @if ($template->key !== 'welcome')
                                        <button
                                            type="button"
                                            class="agricart-categories__action is-danger"
                                            wire:click="delete({{ $template->id }})"
                                            wire:confirm="{{ __('contact_message_templates.confirm_delete') }}"
                                        >
                                            {{ __('contact_message_templates.actions.delete') }}
                                        </button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="agricart-categories__empty-cell">
                                {{ __('contact_message_templates.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
