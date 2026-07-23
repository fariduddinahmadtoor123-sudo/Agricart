<?php

namespace App\Services\Communication;

use App\Models\Communication\Conversation;
use App\Models\Communication\ConversationLink;
use App\Services\Communication\Support\ActorRef;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class LinkService
{
    public function __construct(
        private readonly ChannelRegistry $channels,
        private readonly CommunicationAuthorizer $authorizer,
    ) {}

    public function attach(
        Conversation $conversation,
        ActorRef $actor,
        string $linkableType,
        int $linkableId,
        string $relation = ConversationLink::RELATION_ABOUT,
    ): ConversationLink {
        $this->authorizer->assertCanManageLinks($conversation, $actor);
        $this->authorizer->assertCanLinkRecord($actor, $linkableType, $linkableId);

        if (! $this->channels->isRegisteredLinkableType($linkableType)) {
            throw ValidationException::withMessages([
                'linkable_type' => ['Unknown linkable type.'],
            ]);
        }

        $participant = $this->authorizer->activeParticipant($conversation, $actor);

        return ConversationLink::query()->firstOrCreate(
            [
                'conversation_id' => $conversation->id,
                'linkable_type' => $linkableType,
                'linkable_id' => $linkableId,
                'relation' => $relation,
            ],
            [
                'created_by_participant_id' => $participant?->id,
            ],
        );
    }

    public function detach(
        Conversation $conversation,
        ActorRef $actor,
        string $linkableType,
        int $linkableId,
        string $relation = ConversationLink::RELATION_ABOUT,
    ): void {
        $this->authorizer->assertCanManageLinks($conversation, $actor);

        ConversationLink::query()
            ->where('conversation_id', $conversation->id)
            ->where('linkable_type', $linkableType)
            ->where('linkable_id', $linkableId)
            ->where('relation', $relation)
            ->delete();
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function conversationsFor(string $linkableType, int $linkableId): Collection
    {
        if (! $this->channels->isRegisteredLinkableType($linkableType)) {
            throw ValidationException::withMessages([
                'linkable_type' => ['Unknown linkable type.'],
            ]);
        }

        return Conversation::query()
            ->whereHas('links', function ($query) use ($linkableType, $linkableId): void {
                $query->where('linkable_type', $linkableType)
                    ->where('linkable_id', $linkableId);
            })
            ->with(['channel', 'links'])
            ->latest('last_message_at')
            ->latest('id')
            ->get();
    }
}
