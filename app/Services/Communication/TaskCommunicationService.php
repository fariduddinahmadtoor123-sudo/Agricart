<?php

namespace App\Services\Communication;

use App\Models\Communication\Conversation;
use App\Models\Communication\ConversationLink;
use App\Models\Communication\Message;
use App\Models\Communication\Participant;
use App\Models\Communication\TaskLink;
use App\Models\User;
use App\Services\Communication\Support\ActorRef;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskCommunicationService
{
    public const CHANNEL_KEY = 'task';

    public function __construct(
        private readonly ChannelRegistry $channels,
        private readonly ConversationService $conversations,
        private readonly ParticipantService $participants,
        private readonly MessageService $messages,
        private readonly LinkService $links,
        private readonly TaskBridgeService $tasks,
        private readonly CommunicationAuthorizer $authorizer,
        private readonly ReadStateService $reads,
    ) {}

    public function ensureChannel(): void
    {
        $definition = config('communication.default_channels.'.self::CHANNEL_KEY);

        if (is_array($definition)) {
            $this->channels->syncDefinitions([self::CHANNEL_KEY => $definition]);
        }
    }

    /**
     * @param  list<int>  $assigneeUserIds
     * @param  array{
     *     title: string,
     *     body?: string|null,
     *     priority?: string,
     *     due_at?: \DateTimeInterface|string|null,
     *     linkable_type?: string|null,
     *     linkable_id?: int|null
     * }  $payload
     * @return array{conversation: Conversation, task: TaskLink, message: ?Message}
     */
    public function createTask(User $creator, array $assigneeUserIds, array $payload): array
    {
        $this->ensureChannel();

        $title = trim((string) ($payload['title'] ?? ''));

        if ($title === '' || mb_strlen($title) > 200) {
            throw ValidationException::withMessages([
                'title' => [__('communication.tasks.errors.title')],
            ]);
        }

        $assigneeUserIds = collect($assigneeUserIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0 && $id !== (int) $creator->id)
            ->unique()
            ->values()
            ->all();

        $linkableType = isset($payload['linkable_type']) ? (string) $payload['linkable_type'] : null;
        $linkableId = isset($payload['linkable_id']) ? (int) $payload['linkable_id'] : null;

        if ($linkableType !== null && $linkableType !== '' && ($linkableId === null || $linkableId < 1)) {
            throw ValidationException::withMessages([
                'linkable_id' => [__('communication.tasks.errors.link')],
            ]);
        }

        if ($linkableType !== null && $linkableType !== '') {
            $this->authorizer->assertCanLinkRecord(ActorRef::user($creator->id), $linkableType, (int) $linkableId);
        }

        $members = array_map(
            fn (int $id) => ActorRef::user($id),
            $assigneeUserIds,
        );

        $conversationType = $assigneeUserIds !== []
            ? Conversation::TYPE_GROUP
            : Conversation::TYPE_DIRECT;

        return DB::transaction(function () use ($creator, $members, $title, $payload, $linkableType, $linkableId, $assigneeUserIds, $conversationType): array {
            $conversation = $this->conversations->create(
                self::CHANNEL_KEY,
                ActorRef::user($creator->id),
                $members,
                [
                    'subject' => $title,
                    'type' => $conversationType,
                    'meta' => [
                        'purpose' => 'task',
                        'primary_linkable_type' => $linkableType,
                        'primary_linkable_id' => $linkableId,
                    ],
                ],
            );

            if ($linkableType !== null && $linkableType !== '' && $linkableId !== null) {
                $this->links->attach(
                    $conversation,
                    ActorRef::user($creator->id),
                    $linkableType,
                    $linkableId,
                    ConversationLink::RELATION_ABOUT,
                );
            }

            $primaryAssignee = $assigneeUserIds[0] ?? $creator->id;

            $task = $this->tasks->createFromConversation(
                $conversation,
                ActorRef::user($creator->id),
                [
                    'title' => $title,
                    'assignable_type' => 'user',
                    'assignable_id' => $primaryAssignee,
                    'priority' => $payload['priority'] ?? TaskLink::PRIORITY_NORMAL,
                    'due_at' => $payload['due_at'] ?? null,
                    'meta' => [
                        'assignee_user_ids' => array_values(array_unique(array_merge(
                            [$creator->id],
                            $assigneeUserIds,
                        ))),
                    ],
                ],
            );

            $body = trim((string) ($payload['body'] ?? ''));
            $message = null;

            if ($body !== '') {
                $message = $this->messages->postText(
                    $conversation,
                    ActorRef::user($creator->id),
                    $body,
                );
                $task->update(['message_id' => $message->id]);
            }

            return [
                'conversation' => $conversation->fresh(['channel', 'participants', 'links', 'tasks']) ?? $conversation,
                'task' => $task->fresh(['conversation']) ?? $task,
                'message' => $message,
            ];
        });
    }

    public function postMessage(User $user, TaskLink $task, string $body): Message
    {
        $task->loadMissing('conversation.channel');
        $conversation = $task->conversation;

        if ($conversation === null) {
            throw ValidationException::withMessages([
                'task' => [__('communication.tasks.errors.missing')],
            ]);
        }

        $this->participants->add($conversation, ActorRef::user($user->id), Participant::ROLE_MEMBER);

        return $this->messages->postText(
            $conversation,
            ActorRef::user($user->id),
            $body,
        );
    }

    public function complete(User $user, TaskLink $task): TaskLink
    {
        $task->loadMissing('conversation.channel');
        $this->participants->add($task->conversation, ActorRef::user($user->id), Participant::ROLE_MEMBER);

        return $this->tasks->complete($task, ActorRef::user($user->id));
    }

    public function reopen(User $user, TaskLink $task): TaskLink
    {
        $task->loadMissing('conversation.channel');
        $this->authorizer->assertCanViewConversation($task->conversation, ActorRef::user($user->id));
        $this->participants->add($task->conversation, ActorRef::user($user->id), Participant::ROLE_MEMBER);

        if ($task->status === TaskLink::STATUS_OPEN) {
            return $task;
        }

        $task->update([
            'status' => TaskLink::STATUS_OPEN,
            'completed_by_type' => null,
            'completed_by_id' => null,
            'completed_at' => null,
        ]);

        return $task->fresh() ?? $task;
    }

    /**
     * @return Collection<int, TaskLink>
     */
    public function tasksForUser(User $user, string $status = 'open'): Collection
    {
        $this->ensureChannel();
        $channel = $this->channels->findByKey(self::CHANNEL_KEY);

        if ($channel === null) {
            return collect();
        }

        return TaskLink::query()
            ->with(['conversation.channel', 'conversation.links'])
            ->whereHas('conversation', function ($query) use ($channel, $user): void {
                $query->where('channel_id', $channel->id)
                    ->whereHas('participants', function ($participant) use ($user): void {
                        $participant->where('actor_type', 'user')
                            ->where('actor_id', $user->id)
                            ->whereNull('left_at');
                    });
            })
            ->when($status === 'open', fn ($q) => $q->where('status', TaskLink::STATUS_OPEN))
            ->when($status === 'done', fn ($q) => $q->where('status', TaskLink::STATUS_DONE))
            ->orderByRaw("case when status = 'open' then 0 else 1 end")
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }

    /**
     * @return Collection<int, TaskLink>
     */
    public function tasksForRecord(User $user, string $linkableType, int $linkableId, string $status = 'all'): Collection
    {
        $this->ensureChannel();
        $this->authorizer->assertCanLinkRecord(ActorRef::user($user->id), $linkableType, $linkableId);

        $channel = $this->channels->findByKey(self::CHANNEL_KEY);

        if ($channel === null) {
            return collect();
        }

        return TaskLink::query()
            ->with(['conversation.channel', 'conversation.links', 'conversation.participants'])
            ->whereHas('conversation', function ($query) use ($channel, $linkableType, $linkableId): void {
                $query->where('channel_id', $channel->id)
                    ->whereHas('links', function ($link) use ($linkableType, $linkableId): void {
                        $link->where('linkable_type', $linkableType)
                            ->where('linkable_id', $linkableId);
                    });
            })
            ->when($status === 'open', fn ($q) => $q->where('status', TaskLink::STATUS_OPEN))
            ->when($status === 'done', fn ($q) => $q->where('status', TaskLink::STATUS_DONE))
            ->orderByRaw("case when status = 'open' then 0 else 1 end")
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }

    /**
     * @return Collection<int, Message>
     */
    public function messagesForTask(User $user, TaskLink $task): Collection
    {
        $task->loadMissing('conversation.channel');
        $conversation = $task->conversation;

        if ($conversation === null) {
            return collect();
        }

        $this->authorizer->assertCanViewConversation($conversation, ActorRef::user($user->id));
        $this->participants->add($conversation, ActorRef::user($user->id), Participant::ROLE_MEMBER);
        $this->reads->markRead($conversation, ActorRef::user($user->id));

        return Message::query()
            ->with(['attachments', 'participant'])
            ->where('conversation_id', $conversation->id)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();
    }

    public function findAccessibleTask(User $user, int $taskId): ?TaskLink
    {
        $task = TaskLink::query()
            ->with(['conversation.channel', 'conversation.links'])
            ->find($taskId);

        if ($task === null || $task->conversation === null) {
            return null;
        }

        try {
            $this->authorizer->assertCanViewConversation($task->conversation, ActorRef::user($user->id));
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            return null;
        }

        return $task;
    }
}
