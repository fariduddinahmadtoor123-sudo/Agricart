<?php

namespace App\Services\Staff;

use App\Models\StaffNote;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class StaffNoteService
{
    /**
     * @param  array{
     *     title: string,
     *     body?: string|null,
     *     assigned_to?: int|null,
     *     due_on?: string|null
     * }  $payload
     */
    public function create(User $actor, array $payload): StaffNote
    {
        $title = trim((string) ($payload['title'] ?? ''));

        if ($title === '' || mb_strlen($title) > 200) {
            throw ValidationException::withMessages([
                'title' => [__('staff_notes.errors.title')],
            ]);
        }

        $assignedTo = isset($payload['assigned_to']) ? (int) $payload['assigned_to'] : null;

        if ($assignedTo !== null && $assignedTo > 0) {
            $this->assertAssignable($assignedTo);
        } else {
            $assignedTo = null;
        }

        return StaffNote::query()->create([
            'title' => $title,
            'body' => $this->nullableText($payload['body'] ?? null),
            'created_by' => $actor->id,
            'assigned_to' => $assignedTo,
            'due_on' => ($payload['due_on'] ?? null) ?: null,
            'status' => StaffNote::STATUS_OPEN,
        ]);
    }

    /**
     * @param  array{
     *     title: string,
     *     body?: string|null,
     *     assigned_to?: int|null,
     *     due_on?: string|null
     * }  $payload
     */
    public function update(User $actor, StaffNote $note, array $payload): StaffNote
    {
        $this->assertCanEdit($actor, $note);

        $title = trim((string) ($payload['title'] ?? ''));

        if ($title === '' || mb_strlen($title) > 200) {
            throw ValidationException::withMessages([
                'title' => [__('staff_notes.errors.title')],
            ]);
        }

        $assignedTo = array_key_exists('assigned_to', $payload)
            ? (int) ($payload['assigned_to'] ?: 0)
            : (int) ($note->assigned_to ?? 0);

        if ($assignedTo > 0) {
            $this->assertAssignable($assignedTo);
        } else {
            $assignedTo = null;
        }

        $note->update([
            'title' => $title,
            'body' => $this->nullableText($payload['body'] ?? $note->body),
            'assigned_to' => $assignedTo,
            'due_on' => array_key_exists('due_on', $payload)
                ? ($payload['due_on'] ?: null)
                : $note->due_on,
        ]);

        return $note->fresh(['creator', 'assignee', 'completedByUser']) ?? $note;
    }

    public function forwardTo(User $actor, StaffNote $note, int $assigneeId): StaffNote
    {
        $this->assertCanEdit($actor, $note);
        $this->assertAssignable($assigneeId);

        if ($assigneeId === (int) $actor->id && (int) $note->created_by !== (int) $actor->id) {
            // Allow assigning to self when you are the assignee already editing — OK.
        }

        $note->update([
            'assigned_to' => $assigneeId,
            'status' => StaffNote::STATUS_OPEN,
            'completed_at' => null,
            'completed_by' => null,
        ]);

        return $note->fresh(['creator', 'assignee', 'completedByUser']) ?? $note;
    }

    public function markDone(User $actor, StaffNote $note): StaffNote
    {
        $this->assertCanAct($actor, $note);

        if ($note->isDone()) {
            return $note;
        }

        $note->update([
            'status' => StaffNote::STATUS_DONE,
            'completed_at' => now(),
            'completed_by' => $actor->id,
        ]);

        return $note->fresh(['creator', 'assignee', 'completedByUser']) ?? $note;
    }

    public function reopen(User $actor, StaffNote $note): StaffNote
    {
        $this->assertCanAct($actor, $note);

        if ($note->isOpen()) {
            return $note;
        }

        $note->update([
            'status' => StaffNote::STATUS_OPEN,
            'completed_at' => null,
            'completed_by' => null,
        ]);

        return $note->fresh(['creator', 'assignee', 'completedByUser']) ?? $note;
    }

    public function delete(User $actor, StaffNote $note): void
    {
        $this->assertCanEdit($actor, $note);
        $note->delete();
    }

    /**
     * @return LengthAwarePaginator<int, StaffNote>
     */
    public function paginateForUser(User $user, string $filter = 'open', string $search = ''): LengthAwarePaginator
    {
        return StaffNote::query()
            ->with(['creator', 'assignee', 'completedByUser'])
            ->where(function ($query) use ($user): void {
                $query->where('created_by', $user->id)
                    ->orWhere('assigned_to', $user->id);
            })
            ->when($filter === 'open', fn ($q) => $q->where('status', StaffNote::STATUS_OPEN))
            ->when($filter === 'done', fn ($q) => $q->where('status', StaffNote::STATUS_DONE))
            ->when($filter === 'mine', fn ($q) => $q->where('assigned_to', $user->id)->where('status', StaffNote::STATUS_OPEN))
            ->when($filter === 'created', fn ($q) => $q->where('created_by', $user->id)->where('status', StaffNote::STATUS_OPEN))
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->where('title', 'like', $like)
                        ->orWhere('body', 'like', $like);
                });
            })
            ->orderByRaw("case when status = 'open' then 0 else 1 end")
            ->orderByRaw('case when due_on is null then 1 else 0 end')
            ->orderBy('due_on')
            ->orderByDesc('id')
            ->paginate(20);
    }

    public function canView(User $actor, StaffNote $note): bool
    {
        if ($actor->isSuperAdmin()) {
            return true;
        }

        return (int) $note->created_by === (int) $actor->id
            || (int) $note->assigned_to === (int) $actor->id;
    }

    private function assertCanEdit(User $actor, StaffNote $note): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ((int) $note->created_by === (int) $actor->id) {
            return;
        }

        throw ValidationException::withMessages([
            'note' => [__('staff_notes.errors.forbidden')],
        ]);
    }

    private function assertCanAct(User $actor, StaffNote $note): void
    {
        if (! $this->canView($actor, $note)) {
            throw ValidationException::withMessages([
                'note' => [__('staff_notes.errors.forbidden')],
            ]);
        }
    }

    private function assertAssignable(int $userId): void
    {
        $user = User::query()->find($userId);

        if ($user === null || $user->status !== User::STATUS_ACTIVE || ! $user->canUseAdmin()) {
            throw ValidationException::withMessages([
                'assigned_to' => [__('staff_notes.errors.assignee')],
            ]);
        }
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
