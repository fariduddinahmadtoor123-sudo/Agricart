<?php

namespace App\Services\Communication;

use App\Contracts\Communication\NotifierPort;
use App\Models\Communication\Conversation;
use App\Models\Communication\Message;
use App\Models\Communication\Participant;
use App\Services\Communication\Support\ActorRef;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MessageService
{
    public function __construct(
        private readonly CommunicationAuthorizer $authorizer,
        private readonly NotifierPort $notifier,
    ) {}

    public function postText(
        Conversation $conversation,
        ActorRef $actor,
        string $body,
        ?int $parentId = null,
    ): Message {
        $body = trim($body);

        if ($body === '' || mb_strlen($body) > 10000) {
            throw ValidationException::withMessages([
                'body' => ['Message body must be between 1 and 10000 characters.'],
            ]);
        }

        $this->authorizer->assertCanPost($conversation, $actor);
        $participant = $this->authorizer->activeParticipant($conversation, $actor);

        if ($participant === null) {
            throw ValidationException::withMessages([
                'participant' => ['Participant required.'],
            ]);
        }

        if ($parentId !== null) {
            $parent = Message::query()
                ->where('conversation_id', $conversation->id)
                ->whereKey($parentId)
                ->first();

            if ($parent === null) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Parent message not found in this conversation.'],
                ]);
            }
        }

        return $this->storeMessage(
            $conversation,
            $participant,
            Message::TYPE_TEXT,
            $body,
            parentId: $parentId,
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function postSystem(
        Conversation $conversation,
        string $body,
        ?string $eventKey = null,
        array $meta = [],
    ): Message {
        $body = trim($body);

        if ($body === '') {
            throw ValidationException::withMessages([
                'body' => ['System message body is required.'],
            ]);
        }

        if ($eventKey !== null) {
            $existing = Message::query()
                ->where('conversation_id', $conversation->id)
                ->where('event_key', $eventKey)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        return $this->storeMessage(
            $conversation,
            null,
            Message::TYPE_SYSTEM,
            $body,
            eventKey: $eventKey,
            meta: $meta,
        );
    }

    public function edit(Message $message, ActorRef $actor, string $body): Message
    {
        $body = trim($body);

        if ($message->isDeleted() || $message->message_type === Message::TYPE_SYSTEM) {
            throw ValidationException::withMessages([
                'message' => ['This message cannot be edited.'],
            ]);
        }

        $participant = $this->authorizer->activeParticipant($message->conversation, $actor);

        if ($participant === null || (int) $message->participant_id !== (int) $participant->id) {
            throw ValidationException::withMessages([
                'message' => ['You can only edit your own messages.'],
            ]);
        }

        if ($body === '' || mb_strlen($body) > 10000) {
            throw ValidationException::withMessages([
                'body' => ['Message body must be between 1 and 10000 characters.'],
            ]);
        }

        $message->update([
            'body' => $body,
            'edited_at' => now(),
        ]);

        return $message->fresh() ?? $message;
    }

    public function softDelete(Message $message, ActorRef $actor): Message
    {
        if ($message->isDeleted()) {
            return $message;
        }

        $participant = $this->authorizer->activeParticipant($message->conversation, $actor);

        if ($participant === null) {
            throw ValidationException::withMessages([
                'message' => ['Not a participant.'],
            ]);
        }

        $ownsMessage = (int) $message->participant_id === (int) $participant->id;
        $canManage = $participant->canManage();

        if (! $ownsMessage && ! $canManage) {
            throw ValidationException::withMessages([
                'message' => ['You cannot delete this message.'],
            ]);
        }

        $message->update([
            'deleted_at' => now(),
            'deleted_by_participant_id' => $participant->id,
            'body' => null,
        ]);

        return $message->fresh() ?? $message;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function storeMessage(
        Conversation $conversation,
        ?Participant $participant,
        string $messageType,
        string $body,
        ?int $parentId = null,
        ?string $eventKey = null,
        array $meta = [],
    ): Message {
        return DB::transaction(function () use (
            $conversation,
            $participant,
            $messageType,
            $body,
            $parentId,
            $eventKey,
            $meta,
        ): Message {
            if ($conversation->status === Conversation::STATUS_ARCHIVED) {
                throw ValidationException::withMessages([
                    'conversation' => ['Archived conversations cannot accept new messages.'],
                ]);
            }

            if ($conversation->status === Conversation::STATUS_CLOSED) {
                $conversation->update(['status' => Conversation::STATUS_OPEN]);
            }

            $message = Message::query()->create([
                'uuid' => (string) Str::uuid(),
                'conversation_id' => $conversation->id,
                'participant_id' => $participant?->id,
                'message_type' => $messageType,
                'body' => $body,
                'body_format' => 'plain',
                'parent_id' => $parentId,
                'event_key' => $eventKey,
                'meta' => $meta !== [] ? $meta : null,
            ]);

            $conversation->forceFill([
                'last_message_at' => $message->created_at ?? now(),
                'last_message_id' => $message->id,
                'status' => Conversation::STATUS_OPEN,
            ])->save();

            if ($participant !== null) {
                $participant->update([
                    'last_read_at' => $message->created_at ?? now(),
                    'last_read_message_id' => $message->id,
                ]);
            }

            $recipients = Participant::query()
                ->where('conversation_id', $conversation->id)
                ->whereNull('left_at')
                ->when($participant !== null, fn ($q) => $q->where('id', '!=', $participant->id))
                ->get()
                ->map(fn (Participant $row): array => [
                    'type' => $row->actor_type,
                    'id' => $row->actor_id,
                ])
                ->all();

            $this->notifier->notifyNewMessage($message, $recipients, [
                'conversation_id' => $conversation->id,
                'channel_id' => $conversation->channel_id,
            ]);

            return $message;
        });
    }
}
