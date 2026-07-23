<?php

namespace App\Services\Communication;

use App\Models\Communication\Conversation;
use App\Models\Communication\TaskLink;
use App\Services\Communication\Support\ActorRef;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class TaskBridgeService
{
    public function __construct(
        private readonly CommunicationAuthorizer $authorizer,
    ) {}

    /**
     * @param  array{
     *     title: string,
     *     assignable_type?: string|null,
     *     assignable_id?: int|null,
     *     due_at?: \DateTimeInterface|string|null,
     *     priority?: string,
     *     message_id?: int|null,
     *     meta?: array<string, mixed>|null
     * }  $payload
     */
    public function createFromConversation(
        Conversation $conversation,
        ActorRef $actor,
        array $payload,
    ): TaskLink {
        $conversation->loadMissing('channel');

        if (! $conversation->channel->allows_tasks) {
            throw ValidationException::withMessages([
                'task' => ['Tasks are disabled on this channel.'],
            ]);
        }

        $this->authorizer->assertCanPost($conversation, $actor);

        $title = trim((string) ($payload['title'] ?? ''));

        if ($title === '' || mb_strlen($title) > 200) {
            throw ValidationException::withMessages([
                'title' => ['Task title is required (max 200 characters).'],
            ]);
        }

        $priority = (string) ($payload['priority'] ?? TaskLink::PRIORITY_NORMAL);

        if (! in_array($priority, [
            TaskLink::PRIORITY_LOW,
            TaskLink::PRIORITY_NORMAL,
            TaskLink::PRIORITY_HIGH,
            TaskLink::PRIORITY_URGENT,
        ], true)) {
            throw ValidationException::withMessages([
                'priority' => ['Invalid task priority.'],
            ]);
        }

        $dueAt = $payload['due_at'] ?? null;

        if (is_string($dueAt) && $dueAt !== '') {
            $dueAt = Carbon::parse($dueAt);
        }

        return TaskLink::query()->create([
            'conversation_id' => $conversation->id,
            'message_id' => $payload['message_id'] ?? null,
            'title' => $title,
            'assignable_type' => $payload['assignable_type'] ?? null,
            'assignable_id' => $payload['assignable_id'] ?? null,
            'due_at' => $dueAt,
            'priority' => $priority,
            'status' => TaskLink::STATUS_OPEN,
            'meta' => $payload['meta'] ?? null,
        ]);
    }

    public function complete(TaskLink $task, ActorRef $completedBy): TaskLink
    {
        $task->loadMissing('conversation.channel');
        $this->authorizer->assertCanViewConversation($task->conversation, $completedBy);

        if (! $task->isOpen()) {
            return $task;
        }

        $task->update([
            'status' => TaskLink::STATUS_DONE,
            'completed_by_type' => $completedBy->type,
            'completed_by_id' => $completedBy->id,
            'completed_at' => now(),
        ]);

        return $task->fresh() ?? $task;
    }
}
