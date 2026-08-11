<div class="agricart-staff-notes">
    @error('note')
        <p class="agricart-staff-notes__banner" role="alert">{{ $message }}</p>
    @enderror

    @if ($showForm)
        <div class="agricart-staff-notes__overlay" wire:click.self="closeForm">
            <section class="agricart-staff-notes__panel" role="dialog" aria-modal="true">
                <header class="agricart-staff-notes__panel-head">
                    <div>
                        <h2>{{ $editingId ? __('staff_notes.form.edit_heading') : __('staff_notes.form.create_heading') }}</h2>
                        <p>{{ __('staff_notes.form.lead') }}</p>
                    </div>
                    <button type="button" class="agricart-staff-notes__panel-close" wire:click="closeForm" aria-label="{{ __('staff_notes.actions.cancel') }}">×</button>
                </header>

                <form wire:submit.prevent="save" class="agricart-staff-notes__form">
                    <label>
                        <span>{{ __('staff_notes.fields.title') }}</span>
                        <input type="text" class="fi-input" wire:model="title" maxlength="200" autofocus />
                    </label>
                    @error('title') <p class="agricart-staff-notes__error">{{ $message }}</p> @enderror

                    <label>
                        <span>{{ __('staff_notes.fields.body') }}</span>
                        <textarea rows="6" class="fi-input" wire:model="body" placeholder="{{ __('staff_notes.fields.body_placeholder') }}"></textarea>
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
                        <button type="button" class="agricart-categories__action" wire:click="closeForm">
                            {{ __('staff_notes.actions.cancel') }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    @if ($viewingNote)
        <div class="agricart-staff-notes__overlay" wire:click.self="closeView">
            <section class="agricart-staff-notes__panel" role="dialog" aria-modal="true">
                <header class="agricart-staff-notes__panel-head">
                    <div>
                        <span @class(['agricart-staff-notes__status', 'is-'.$viewingNote->status])>
                            {{ __('staff_notes.statuses.'.$viewingNote->status) }}
                        </span>
                        <h2>{{ $viewingNote->title }}</h2>
                    </div>
                    <button type="button" class="agricart-staff-notes__panel-close" wire:click="closeView" aria-label="{{ __('staff_notes.actions.cancel') }}">×</button>
                </header>

                @if (filled($viewingNote->body))
                    <p class="agricart-staff-notes__view-body">{{ $viewingNote->body }}</p>
                @endif

                <p class="agricart-staff-notes__meta">
                    {{ __('staff_notes.list.created_by', ['name' => $viewingNote->creator?->name ?? '—']) }}
                    @if ($viewingNote->assignee)
                        · {{ __('staff_notes.list.assigned_to', ['name' => $viewingNote->assignee->name]) }}
                    @endif
                    @if ($viewingNote->due_on)
                        · {{ __('staff_notes.list.due', ['date' => $viewingNote->due_on->format('d M Y')]) }}
                    @endif
                    @if ($viewingNote->isDone() && $viewingNote->completedByUser)
                        · {{ __('staff_notes.list.done_by', ['name' => $viewingNote->completedByUser->name]) }}
                    @endif
                </p>

                <div class="agricart-staff-notes__item-actions">
                    <a
                        href="{{ route('admin.staff-notes.print', $viewingNote) }}"
                        class="agricart-categories__action"
                        target="_blank"
                        rel="noopener noreferrer"
                    >{{ __('staff_notes.actions.print') }}</a>

                    @if ($viewingNote->isOpen())
                        <button type="button" class="agricart-categories__action is-primary" wire:click="markDone({{ $viewingNote->id }})">
                            {{ __('staff_notes.actions.mark_done') }}
                        </button>
                    @else
                        <button type="button" class="agricart-categories__action" wire:click="reopen({{ $viewingNote->id }})">
                            {{ __('staff_notes.actions.reopen') }}
                        </button>
                    @endif

                    @if ($authUser && ($authUser->isSuperAdmin() || (int) $viewingNote->created_by === (int) $authUser->id))
                        <button type="button" class="agricart-categories__action" wire:click="startEdit({{ $viewingNote->id }})">
                            {{ __('staff_notes.actions.edit') }}
                        </button>
                        <button type="button" class="agricart-categories__action" wire:click="startForward({{ $viewingNote->id }})">
                            {{ __('staff_notes.actions.forward') }}
                        </button>
                        <button
                            type="button"
                            class="agricart-categories__action is-danger"
                            wire:click="deleteNote({{ $viewingNote->id }})"
                            wire:confirm="{{ __('staff_notes.actions.delete_confirm') }}"
                        >{{ __('staff_notes.actions.delete') }}</button>
                    @endif
                </div>

                @if ($forwardNoteId === $viewingNote->id)
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
            </section>
        </div>
    @endif

    <section class="agricart-staff-notes__list-wrap">
        <header class="agricart-staff-notes__list-head">
            <div>
                <h2>{{ __('staff_notes.list.heading') }}</h2>
                <p>{{ __('staff_notes.list.lead') }}</p>
            </div>
            <button
                type="button"
                class="agricart-categories__action is-primary"
                wire:click="openCreateForm"
            >{{ __('staff_notes.actions.add_note') }}</button>
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
                                <p>{{ \Illuminate\Support\Str::limit($note->body, 140) }}</p>
                            @endif
                            <p class="agricart-staff-notes__meta">
                                {{ __('staff_notes.list.created_by', ['name' => $note->creator?->name ?? '—']) }}
                                @if ($note->assignee)
                                    · {{ __('staff_notes.list.assigned_to', ['name' => $note->assignee->name]) }}
                                @endif
                                @if ($note->due_on)
                                    · {{ __('staff_notes.list.due', ['date' => $note->due_on->format('d M Y')]) }}
                                @endif
                            </p>
                        </div>

                        <div class="agricart-staff-notes__item-actions">
                            <button type="button" class="agricart-categories__action is-primary" wire:click="viewNote({{ $note->id }})">
                                {{ __('staff_notes.actions.view') }}
                            </button>
                            <a
                                href="{{ route('admin.staff-notes.print', $note) }}"
                                class="agricart-categories__action"
                                target="_blank"
                                rel="noopener noreferrer"
                            >{{ __('staff_notes.actions.print') }}</a>
                            @if ($note->isOpen())
                                <button type="button" class="agricart-categories__action" wire:click="markDone({{ $note->id }})">
                                    {{ __('staff_notes.actions.mark_done') }}
                                </button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="agricart-categories__pagination">
                {{ $rows->links() }}
            </div>
        @endif
    </section>
</div>
