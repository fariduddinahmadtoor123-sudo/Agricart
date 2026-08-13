<?php

namespace App\Services\Communication;

use App\Models\Communication\Channel;
use App\Models\Communication\Conversation;
use App\Models\Communication\Participant;
use App\Models\User;
use App\Services\Access\ModulePageAccess;
use App\Services\Communication\Support\ActorRef;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CommunicationAuthorizer
{
    public function __construct(
        private readonly ChannelRegistry $channels,
    ) {}

    public function assertChannelAccess(Channel $channel, ActorRef $actor): void
    {
        if (! $channel->allowsActorType($actor->type)) {
            throw new AuthorizationException('This actor type is not allowed on this channel.');
        }

        if ($actor->type === 'user') {
            $user = $this->user($actor);

            if ($user->isSuperAdmin() || $user->hasPermission('comms.access')) {
                return;
            }

            // Staff messenger / staff_ops DMs: equal access for every active admin — no role toggles.
            if ($user->canUseAdmin() && (
                $channel->key === DirectStaffMessengerService::CHANNEL_KEY
                || ($channel->settings['allow_any_admin'] ?? false) === true
            )) {
                return;
            }

            $permission = $channel->permissionKey();
            $fallbacks = $channel->settings['fallback_permissions'] ?? [];

            if (! is_array($fallbacks)) {
                $fallbacks = [];
            }

            if ($permission !== null && ModulePageAccess::userHasKey($user, $permission)) {
                return;
            }

            foreach ($fallbacks as $fallback) {
                if (is_string($fallback) && $fallback !== '' && ModulePageAccess::userHasKey($user, $fallback)) {
                    return;
                }
            }

            if ($permission !== null || $fallbacks !== []) {
                throw new AuthorizationException('Missing channel permission.');
            }
        }
    }

    public function assertCanViewConversation(Conversation $conversation, ActorRef $actor): void
    {
        $conversation->loadMissing('channel');
        $this->assertChannelAccess($conversation->channel, $actor);

        $participant = $this->activeParticipant($conversation, $actor);

        if ($participant !== null) {
            return;
        }

        if ($conversation->channel->visibility === 'record_acl' && $actor->type === 'user') {
            if ($this->userCanAccessAnyLinkedRecord($conversation, $this->user($actor))) {
                return;
            }
        }

        throw new AuthorizationException('Not a participant of this conversation.');
    }

    public function assertCanPost(Conversation $conversation, ActorRef $actor): void
    {
        $this->assertCanViewConversation($conversation, $actor);

        $participant = $this->activeParticipant($conversation, $actor);

        if ($participant === null || ! $participant->canPost()) {
            throw new AuthorizationException('This participant cannot post messages.');
        }
    }

    public function assertCanManageLinks(Conversation $conversation, ActorRef $actor): void
    {
        $this->assertCanViewConversation($conversation, $actor);

        $participant = $this->activeParticipant($conversation, $actor);

        if ($participant === null || ! in_array($participant->role, [
            Participant::ROLE_OWNER,
            Participant::ROLE_MEMBER,
        ], true)) {
            throw new AuthorizationException('This participant cannot manage links.');
        }
    }

    public function assertCanLinkRecord(ActorRef $actor, string $linkableType, int $linkableId): void
    {
        if (! $this->channels->isRegisteredLinkableType($linkableType)) {
            throw new AuthorizationException('Unknown linkable type.');
        }

        if ($actor->type !== 'user') {
            return;
        }

        $user = $this->user($actor);

        if ($user->isSuperAdmin()) {
            $this->assertRecordExists($linkableType, $linkableId);

            return;
        }

        $permission = config("communication.linkable_permissions.{$linkableType}");

        if (is_string($permission) && $permission !== '' && ! ModulePageAccess::userHasKey($user, $permission)) {
            throw new AuthorizationException('Missing permission to link this record.');
        }

        if (is_array($permission)) {
            $allowed = false;

            foreach ($permission as $key) {
                if (is_string($key) && $key !== '' && ModulePageAccess::userHasKey($user, $key)) {
                    $allowed = true;
                    break;
                }
            }

            if (! $allowed && $permission !== []) {
                throw new AuthorizationException('Missing permission to link this record.');
            }
        }

        $this->assertRecordExists($linkableType, $linkableId);
    }

    public function activeParticipant(Conversation $conversation, ActorRef $actor): ?Participant
    {
        return Participant::query()
            ->where('conversation_id', $conversation->id)
            ->where('actor_type', $actor->type)
            ->where('actor_id', $actor->id)
            ->whereNull('left_at')
            ->first();
    }

    private function user(ActorRef $actor): User
    {
        $user = User::query()->find($actor->id);

        if (! $user instanceof User) {
            throw new AuthorizationException('User actor not found.');
        }

        return $user;
    }

    private function assertRecordExists(string $linkableType, int $linkableId): void
    {
        $class = $this->channels->linkableModelClass($linkableType);
        /** @var class-string<Model> $class */
        $exists = $class::query()->whereKey($linkableId)->exists();

        if (! $exists) {
            throw new AuthorizationException('Linked record does not exist.');
        }
    }

    private function userCanAccessAnyLinkedRecord(Conversation $conversation, User $user): bool
    {
        if (! Schema::hasTable('comm_conversation_links')) {
            return false;
        }

        foreach ($conversation->links as $link) {
            try {
                $this->assertCanLinkRecord(ActorRef::user($user->id), $link->linkable_type, (int) $link->linkable_id);

                return true;
            } catch (AuthorizationException) {
                continue;
            }
        }

        return false;
    }
}
