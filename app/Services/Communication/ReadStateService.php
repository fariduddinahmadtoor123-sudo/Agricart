<?php

namespace App\Services\Communication;

use App\Models\Communication\Conversation;
use App\Models\Communication\Message;
use App\Models\Communication\Participant;
use App\Services\Communication\Support\ActorRef;
use Illuminate\Support\Facades\Schema;

class ReadStateService
{
    public function __construct(
        private readonly CommunicationAuthorizer $authorizer,
    ) {}

    public function markRead(Conversation $conversation, ActorRef $actor, ?int $messageId = null): void
    {
        $this->authorizer->assertCanViewConversation($conversation, $actor);
        $participant = $this->authorizer->activeParticipant($conversation, $actor);

        if ($participant === null) {
            return;
        }

        $message = null;

        if ($messageId !== null) {
            $message = Message::query()
                ->where('conversation_id', $conversation->id)
                ->whereKey($messageId)
                ->first();
        } elseif ($conversation->last_message_id !== null) {
            $message = Message::query()->find($conversation->last_message_id);
        }

        $participant->update([
            'last_read_at' => now(),
            'last_read_message_id' => $message?->id ?? $participant->last_read_message_id,
        ]);
    }

    public function unreadConversationCount(ActorRef $actor): int
    {
        if (! Schema::hasTable('comm_participants')) {
            return 0;
        }

        return Participant::query()
            ->where('comm_participants.actor_type', $actor->type)
            ->where('comm_participants.actor_id', $actor->id)
            ->whereNull('comm_participants.left_at')
            ->whereNotNull('comm_conversations.last_message_id')
            ->whereNull('comm_conversations.deleted_at')
            ->where(function ($query): void {
                $query->whereNull('comm_participants.last_read_message_id')
                    ->orWhereColumn('comm_participants.last_read_message_id', '<', 'comm_conversations.last_message_id');
            })
            ->join('comm_conversations', 'comm_conversations.id', '=', 'comm_participants.conversation_id')
            ->count('comm_participants.id');
    }

    public function isUnreadFor(Conversation $conversation, ActorRef $actor): bool
    {
        $participant = $this->authorizer->activeParticipant($conversation, $actor);

        if ($participant === null || $conversation->last_message_id === null) {
            return false;
        }

        return $participant->last_read_message_id === null
            || (int) $participant->last_read_message_id < (int) $conversation->last_message_id;
    }
}
