<?php

namespace App\Livewire\Dashboard;

use App\Models\StaffNote;
use App\Models\User;
use App\Services\Staff\StaffNoteService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListStaffNotes extends Component
{
    use WithPagination;

    public string $search = '';

    /** @var 'open'|'done'|'all'|'mine'|'created' */
    public string $filter = 'open';

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?int $viewingId = null;

    public string $title = '';

    public string $body = '';

    public ?int $assignedTo = null;

    public string $dueOn = '';

    public ?int $forwardNoteId = null;

    public ?int $forwardTo = null;

    #[On('staff-notes-search')]
    public function updateSearch(string $query): void
    {
        $this->search = trim($query);
        $this->resetPage();
    }

    #[On('staff-notes-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'status' && in_array($value, ['open', 'done', 'all', 'mine', 'created'], true)) {
            $this->filter = $value;
            $this->resetPage();
        }
    }

    #[On('staff-notes-clear-filters')]
    public function clearFilters(): void
    {
        $this->filter = 'open';
        $this->resetPage();
    }

    #[On('staff-notes-open-form')]
    public function openCreateForm(): void
    {
        $this->startCreate();
        $this->showForm = true;
        $this->viewingId = null;
        $this->forwardNoteId = null;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->startCreate();
    }

    public function startCreate(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->body = '';
        $this->assignedTo = null;
        $this->dueOn = '';
        $this->forwardNoteId = null;
        $this->resetValidation();
    }

    public function viewNote(int $noteId): void
    {
        $user = $this->authUser();
        $note = StaffNote::query()->find($noteId);

        if ($user === null || $note === null) {
            return;
        }

        $this->viewingId = $note->id;
        $this->showForm = false;
        $this->forwardNoteId = null;
        $this->resetValidation();
    }

    public function closeView(): void
    {
        $this->viewingId = null;
        $this->forwardNoteId = null;
    }

    public function startEdit(int $noteId): void
    {
        $user = $this->authUser();
        $note = StaffNote::query()->find($noteId);

        if ($user === null || $note === null) {
            return;
        }

        if (! $user->isSuperAdmin() && (int) $note->created_by !== (int) $user->id) {
            return;
        }

        $this->editingId = $note->id;
        $this->title = $note->title;
        $this->body = (string) ($note->body ?? '');
        $this->assignedTo = $note->assigned_to;
        $this->dueOn = $note->due_on?->format('Y-m-d') ?? '';
        $this->forwardNoteId = null;
        $this->viewingId = null;
        $this->showForm = true;
        $this->resetValidation();
    }

    public function save(StaffNoteService $notes): void
    {
        $user = $this->authUser();

        if ($user === null) {
            abort(403);
        }

        $payload = [
            'title' => $this->title,
            'body' => $this->body,
            'assigned_to' => $this->assignedTo,
            'due_on' => $this->dueOn !== '' ? $this->dueOn : null,
        ];

        try {
            if ($this->editingId !== null) {
                $note = StaffNote::query()->findOrFail($this->editingId);
                $notes->update($user, $note, $payload);
            } else {
                $notes->create($user, $payload);
            }
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
        }

        $this->closeForm();
        $this->resetPage();
    }

    public function markDone(int $noteId, StaffNoteService $notes): void
    {
        $user = $this->authUser();
        $note = StaffNote::query()->find($noteId);

        if ($user === null || $note === null) {
            return;
        }

        try {
            $notes->markDone($user, $note);
        } catch (ValidationException $exception) {
            $this->addError('note', collect($exception->errors())->flatten()->first() ?: __('staff_notes.errors.forbidden'));
        }
    }

    public function reopen(int $noteId, StaffNoteService $notes): void
    {
        $user = $this->authUser();
        $note = StaffNote::query()->find($noteId);

        if ($user === null || $note === null) {
            return;
        }

        try {
            $notes->reopen($user, $note);
        } catch (ValidationException $exception) {
            $this->addError('note', collect($exception->errors())->flatten()->first() ?: __('staff_notes.errors.forbidden'));
        }
    }

    public function startForward(int $noteId): void
    {
        $this->forwardNoteId = $noteId;
        $this->forwardTo = null;
        $this->resetValidation();
    }

    public function cancelForward(): void
    {
        $this->forwardNoteId = null;
        $this->forwardTo = null;
    }

    public function confirmForward(StaffNoteService $notes): void
    {
        $user = $this->authUser();
        $note = $this->forwardNoteId !== null
            ? StaffNote::query()->find($this->forwardNoteId)
            : null;

        if ($user === null || $note === null || ! $this->forwardTo) {
            $this->addError('forwardTo', __('staff_notes.errors.assignee'));

            return;
        }

        try {
            $notes->forwardTo($user, $note, (int) $this->forwardTo);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field === 'assigned_to' ? 'forwardTo' : $field, $message);
                }
            }

            return;
        }

        $this->cancelForward();
    }

    public function deleteNote(int $noteId, StaffNoteService $notes): void
    {
        $user = $this->authUser();
        $note = StaffNote::query()->find($noteId);

        if ($user === null || $note === null) {
            return;
        }

        try {
            $notes->delete($user, $note);
        } catch (ValidationException $exception) {
            $this->addError('note', collect($exception->errors())->flatten()->first() ?: __('staff_notes.errors.forbidden'));

            return;
        }

        if ($this->editingId === $noteId) {
            $this->closeForm();
        }

        if ($this->viewingId === $noteId) {
            $this->closeView();
        }
    }

    public function render(StaffNoteService $notes): View
    {
        $user = $this->authUser();
        $rows = $user !== null
            ? $notes->paginateForUser($user, $this->filter, $this->search)
            : StaffNote::query()->whereRaw('1 = 0')->paginate(20);

        $staffOptions = User::query()
            ->where('status', User::STATUS_ACTIVE)
            ->whereNotNull('role_id')
            ->when($user !== null, fn ($q) => $q->whereKeyNot($user->id))
            ->orderBy('name')
            ->limit(80)
            ->get(['id', 'name']);

        $viewingNote = null;

        if ($this->viewingId !== null) {
            $viewingNote = StaffNote::query()
                ->with(['creator', 'assignee', 'completedByUser'])
                ->find($this->viewingId);

            if ($viewingNote === null) {
                $this->viewingId = null;
            }
        }

        return view('livewire.dashboard.list-staff-notes', [
            'rows' => $rows,
            'authUser' => $user,
            'staffOptions' => $staffOptions,
            'viewingNote' => $viewingNote,
        ]);
    }

    private function authUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
