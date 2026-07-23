<div class="agricart-staff-notes">
    @error('note')
        <p class="agricart-staff-notes__banner" role="alert">{{ $message }}</p>
    @enderror

    <div class="agricart-staff-notes__layout">
        <section class="agricart-staff-notes__compose">
            <h2>{{ $editingId ? __('staff_notes.form.edit_heading') : __('staff_notes.form.create_heading') }}</h2>
            <p>{{ __('staff_notes.form.lead') }}</p>

            <form wire:submit.prevent="save" class="agricart-staff-notes__form">
                <label>
                    <span>{{ __('staff_notes.fields.title') }}</span>
                    <input type="text" class="fi-input" wire:model="title" maxlength="200" />
                </label>
                @error('title') <p class="agricart-staff-notes__error">{{ $message }}</p> @enderror

                <label>
                    <span>{{ __('staff_notes.fields.body') }}</span>
                    <textarea rows="4" class="fi-input" wire:model="body" placeholder="{{ __('staff_notes.fields.body_placeholder') }}"></textarea>
                </label>

                <label>
                    <span>{{ __('staff_notes.fields.assigned_to') }}</span>
                    <select class="fi-select" wire:model="assignedTo">
                        <option value="">{{ __('staff_notes.fields.assigned_none') }}</option>
                        @foreach ($staffOptions as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                        @endforeach
                    </select>
                </label>
                @error('assigned_to') <p class="agricart-staff-notes__error">{{ $message }}</p> @enderror

                <label>
                    <span>{{ __('staff_notes.fields.due_on') }}</span>
                    <input type="date" class="fi-input" wire:model="dueOn" />
                </label>

                <div class="agricart-staff-notes__form-actions">
                    <button type="submit" class="agricart-categories__action is-primary">
                        {{ $editingId ? __('staff_notes.actions.save') : __('staff_notes.actions.add') }}
                    </button>
                    @if ($editingId)
                        <button type="button" class="agricart-categories__action" wire:click="startCreate">
                            {{ __('staff_notes.actions.cancel_edit') }}
                        </button>
                    @endif
                </div>
            </form>
        </section>

        <section class="agricart-staff-notes__list-wrap">
            <header class="agricart-staff-notes__list-head">
                <h2>{{ __('staff_notes.list.heading') }}</h2>
                <p>{{ __('staff_notes.list.lead') }}</p>
            </header>

            @if ($rows->isEmpty())
                <div class="agricart-categories__empty">
                    <p>{{ __('staff_notes.list.empty') }}</p>
                </div>
            @else
                <ul class="agricart-staff-notes__list">
                    @foreach ($rows as $note)
                        <li wire:key="staff-note-{{ $note->id }}" @class(['agricart-staff-notes__item', 'is-done' => $note->isDone()])>
                            <div class="agricart-staff-notes__item-main">
                                <span @class(['agricart-staff-notes__status', 'is-'.$note->status])>
                                    {{ __('staff_notes.statuses.'.$note->status) }}
                                </span>
                                <strong>{{ $note->title }}</strong>
                                @if (filled($note->body))
                                    <p>{{ $note->body }}</p>
                                @endif
                                <p class="agricart-staff-notes__meta">
                                    {{ __('staff_notes.list.created_by', ['name' => $note->creator?->name ?? '—']) }}
                                    @if ($note->assignee)
                                        · {{ __('staff_notes.list.assigned_to', ['name' => $note->assignee->name]) }}
                                    @endif
                                    @if ($note->due_on)
                                        · {{ __('staff_notes.list.due', ['date' => $note->due_on->format('d M Y')]) }}
                                    @endif
                                    @if ($note->isDone() && $note->completedByUser)
                                        · {{ __('staff_notes.list.done_by', ['name' => $note->completedByUser->name]) }}
                                    @endif
                                </p>
                            </div>

                            <div class="agricart-staff-notes__item-actions">
                                @if ($note->isOpen())
                                    <button type="button" class="agricart-categories__action is-primary" wire:click="markDone({{ $note->id }})">
                                        {{ __('staff_notes.actions.mark_done') }}
                                    </button>
                                @else
                                    <button type="button" class="agricart-categories__action" wire:click="reopen({{ $note->id }})">
                                        {{ __('staff_notes.actions.reopen') }}
                                    </button>
                                @endif

                                @if ($authUser && ($authUser->isSuperAdmin() || (int) $note->created_by === (int) $authUser->id))
                                    <button type="button" class="agricart-categories__action" wire:click="startEdit({{ $note->id }})">
                                        {{ __('staff_notes.actions.edit') }}
                                    </button>
                                    <button type="button" class="agricart-categories__action" wire:click="startForward({{ $note->id }})">
                                        {{ __('staff_notes.actions.forward') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="agricart-categories__action is-danger"
                                        wire:click="deleteNote({{ $note->id }})"
                                        wire:confirm="{{ __('staff_notes.actions.delete_confirm') }}"
                                    >{{ __('staff_notes.actions.delete') }}</button>
                                @endif
                            </div>

                            @if ($forwardNoteId === $note->id)
                                <form class="agricart-staff-notes__forward" wire:submit.prevent="confirmForward">
                                    <label>
                                        <span>{{ __('staff_notes.fields.forward_to') }}</span>
                                        <select class="fi-select" wire:model="forwardTo">
                                            <option value="">{{ __('staff_notes.fields.assigned_none') }}</option>
                                            @foreach ($staffOptions as $staff)
                                                <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    @error('forwardTo') <p class="agricart-staff-notes__error">{{ $message }}</p> @enderror
                                    <div class="agricart-staff-notes__form-actions">
                                        <button type="submit" class="agricart-categories__action is-primary">{{ __('staff_notes.actions.send_forward') }}</button>
                                        <button type="button" class="agricart-categories__action" wire:click="cancelForward">{{ __('staff_notes.actions.cancel') }}</button>
                                    </div>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <div class="agricart-categories__pagination">
                    {{ $rows->links() }}
                </div>
            @endif
        </section>
    </div>
</div>
