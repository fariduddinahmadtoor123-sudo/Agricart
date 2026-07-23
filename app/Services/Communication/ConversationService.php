<?php

namespace App\Services\Communication;

use App\Models\Communication\Conversation;
use App\Models\Communication\Participant;
use App\Services\Communication\Support\ActorRef;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ConversationService
{
    public function __construct(
        private readonly ChannelRegistry $channels,
        private readonly CommunicationAuthorizer $authorizer,
        private readonly ParticipantService $participants,
    ) {}

    /**
     * @param  list<ActorRef>  $members
     * @param  array{subject?: string|null, type?: string, meta?: array<string, mixed>|null}  $options
     */
    public function create(string $channelKey, ActorRef $creator, array $members, array $options = []): Conversation
    {
        $channel = $this->channels->requireByKey($channelKey);
        $this->authorizer->assertChannelAccess($channel, $creator);

        $type = (string) ($options['type'] ?? Conversation::TYPE_DIRECT);
        $uniqueMembers = $this->uniqueActors(array_merge([$creator], $members));

        if ($type === Conversation::TYPE_GROUP && ! $channel->allows_groups) {
            throw ValidationException::withMessages([
                'type' => ['This channel does not allow group conversations.'],
            ]);
        }

        if ($type === Conversation::TYPE_DIRECT && count($uniqueMembers) > 2) {
            throw ValidationException::withMessages([
                'members' => ['Direct conversations allow at most two participants.'],
            ]);
        }

        foreach ($uniqueMembers as $member) {
            if (! $this->channels->isRegisteredActorType($member->type)) {
                throw ValidationException::withMessages([
                    'members' => ["Unknown actor type [{$member->type}]."],
                ]);
            }

            if (! $channel->allowsActorType($member->type)) {
                throw ValidationException::withMessages([
                    'members' => ["Actor type [{$member->type}] is not allowed on channel [{$channelKey}]."],
                ]);
            }
        }

        return DB::transaction(function () use ($channel, $creator, $uniqueMembers, $options, $type): Conversation {
            $conversation = Conversation::query()->create([
                'uuid' => (string) Str::uuid(),
                'channel_id' => $channel->id,
                'subject' => isset($options['subject']) ? trim((string) $options['subject']) : null,
                'type' => $type,
                'status' => Conversation::STATUS_OPEN,
                'meta' => $options['meta'] ?? null,
            ]);

            $owner = null;

            foreach ($uniqueMembers as $member) {
                $role = $member->type === $creator->type && $member->id === $creator->id
                    ? Participant::ROLE_OWNER
                    : Participant::ROLE_MEMBER;

                $participant = $this->participants->add($conversation, $member, $role);

                if ($role === Participant::ROLE_OWNER) {
                    $owner = $participant;
                }
            }

            if ($owner !== null) {
                $conversation->update(['created_by_participant_id' => $owner->id]);
            }

            return $conversation->fresh(['channel', 'participants']) ?? $conversation;
        });
    }

    public function close(Conversation $conversation, ActorRef $actor): Conversation
    {
        $this->authorizer->assertCanViewConversation($conversation, $actor);
        $conversation->update(['status' => Conversation::STATUS_CLOSED]);

        return $conversation->fresh() ?? $conversation;
    }

    public function reopen(Conversation $conversation, ActorRef $actor): Conversation
    {
        $this->authorizer->assertCanViewConversation($conversation, $actor);
        $conversation->update(['status' => Conversation::STATUS_OPEN]);

        return $conversation->fresh() ?? $conversation;
    }

    public function archive(Conversation $conversation, ActorRef $actor): Conversation
    {
        $this->authorizer->assertCanViewConversation($conversation, $actor);
        $conversation->update(['status' => Conversation::STATUS_ARCHIVED]);

        return $conversation->fresh() ?? $conversation;
    }

    /**
     * @param  list<ActorRef>  $actors
     * @return list<ActorRef>
     */
    private function uniqueActors(array $actors): array
    {
        $seen = [];
        $unique = [];

        foreach ($actors as $actor) {
            $key = $actor->type.':'.($actor->id ?? 'null');

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $actor;
        }

        return $unique;
    }
}
