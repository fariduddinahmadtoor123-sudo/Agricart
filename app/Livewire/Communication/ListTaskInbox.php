<?php

namespace App\Livewire\Communication;

use App\Models\Communication\Message;
use App\Models\Communication\TaskLink;
use App\Models\User;
use App\Services\Communication\TaskCommunicationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListTaskInbox extends Component
{
    use WithPagination;

    public string $search = '';

    /** @var 'all'|'open'|'done' */
    public string $filter = 'open';

    public string $title = '';

    public string $body = '';

    public string $priority = TaskLink::PRIORITY_NORMAL;

    /** @var list<int|string> */
    public array $assigneeIds = [];

    public ?int $openTaskId = null;

    public string $replyText = '';

    #[On('task-inbox-search')]
    public function updateSearch(string $query): void
    {
        $this->search = trim($query);
        $this->resetPage();
    }

    #[On('task-inbox-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'status' && in_array($value, ['all', 'open', 'done'], true)) {
            $this->filter = $value;
            $this->resetPage();
        }
    }

    #[On('task-inbox-clear-filters')]
    public function clearFilters(): void
    {
        $this->filter = 'open';
        $this->resetPage();
    }

    public function createTask(TaskCommunicationService $tasks): void
    {
        $user = $this->authUser();

        if ($user === null) {
            abort(403);
        }

        try {
            $result = $tasks->createTask(
                $user,
                array_map('intval', $this->assigneeIds),
                [
                    'title' => $this->title,
                    'body' => $this->body,
                    'priority' => $this->priority,
                ],
            );
        } catch (ValidationException $exception) {
            $this->applyValidationErrors($exception);

            return;
        }

        $this->title = '';
        $this->body = '';
        $this->assigneeIds = [];
        $this->priority = TaskLink::PRIORITY_NORMAL;
        $this->openTaskId = $result['task']->id;
        $this->replyText = '';
        $this->resetValidation();
    }

    public function openTask(int $taskId): void
    {
        $this->openTaskId = $taskId;
        $this->replyText = '';
        $this->resetValidation();
    }

    public function closeTask(): void
    {
        $this->openTaskId = null;
        $this->replyText = '';
    }

    public function sendReply(TaskCommunicationService $tasks): void
    {
        $user = $this->authUser();
        $task = $this->resolveOpenTask($tasks, $user);

        if ($user === null || $task === null) {
            return;
        }

        try {
            $tasks->postMessage($user, $task, $this->replyText);
        } catch (ValidationException $exception) {
            $this->applyValidationErrors($exception);

            return;
        }

        $this->replyText = '';
        $this->resetValidation();
    }

    public function completeTask(TaskCommunicationService $tasks): void
    {
        $user = $this->authUser();
        $task = $this->resolveOpenTask($tasks, $user);

        if ($user === null || $task === null) {
            return;
        }

        $tasks->complete($user, $task);
    }

    public function reopenTask(TaskCommunicationService $tasks): void
    {
        $user = $this->authUser();
        $task = $this->resolveOpenTask($tasks, $user);

        if ($user === null || $task === null) {
            return;
        }

        $tasks->reopen($user, $task);
    }

    public function render(TaskCommunicationService $tasks): View
    {
        $user = $this->authUser();
        /** @var Collection<int, TaskLink> $taskList */
        $taskList = collect();
        $openTask = null;
        /** @var Collection<int, Message> $messages */
        $messages = collect();
        $authorNames = [];
        $staffOptions = [];

        if ($user !== null) {
            $taskList = $tasks->tasksForUser($user, $this->filter === 'all' ? 'all' : $this->filter);

            if ($this->search !== '') {
                $needle = mb_strtolower($this->search);
                $taskList = $taskList->filter(function (TaskLink $task) use ($needle): bool {
                    return str_contains(mb_strtolower($task->title), $needle)
                        || str_contains(mb_strtolower((string) $task->conversation?->subject), $needle);
                })->values();
            }

            $openTask = $this->openTaskId !== null
                ? $tasks->findAccessibleTask($user, $this->openTaskId)
                : null;

            if ($openTask !== null) {
                $messages = $tasks->messagesForTask($user, $openTask);
                $userIds = $messages
                    ->map(fn (Message $message) => $message->participant?->actor_type === 'user'
                        ? $message->participant->actor_id
                        : null)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if ($userIds !== []) {
                    $authorNames = User::query()->whereIn('id', $userIds)->pluck('name', 'id')->all();
                }
            }

            $staffOptions = User::query()
                ->where('status', User::STATUS_ACTIVE)
                ->whereKeyNot($user->id)
                ->orderBy('name')
                ->limit(80)
                ->get(['id', 'name'])
                ->map(fn (User $staff): array => [
                    'id' => $staff->id,
                    'name' => (string) $staff->name,
                ])
                ->all();
        }

        return view('livewire.communication.list-task-inbox', [
            'taskList' => $taskList,
            'openTask' => $openTask,
            'messages' => $messages,
            'authUser' => $user,
            'authorNames' => $authorNames,
            'staffOptions' => $staffOptions,
            'priorities' => [
                TaskLink::PRIORITY_LOW,
                TaskLink::PRIORITY_NORMAL,
                TaskLink::PRIORITY_HIGH,
                TaskLink::PRIORITY_URGENT,
            ],
        ]);
    }

    private function resolveOpenTask(TaskCommunicationService $tasks, ?User $user): ?TaskLink
    {
        if ($user === null || $this->openTaskId === null) {
            return null;
        }

        return $tasks->findAccessibleTask($user, $this->openTaskId);
    }

    private function applyValidationErrors(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            foreach ($messages as $message) {
                $this->addError($field, $message);
            }
        }
    }

    private function authUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
