<?php

namespace App\Services\Communication;

use App\Models\Communication\Attachment;
use App\Models\Communication\Conversation;
use App\Models\Communication\ConversationLink;
use App\Models\Communication\Message;
use App\Models\Communication\Participant;
use App\Models\User;
use App\Services\Communication\Support\ActorRef;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class RecordChannelThreadService
{
    public function __construct(
        private readonly ChannelRegistry $channels,
        private readonly ConversationService $conversations,
        private readonly ParticipantService $participants,
        private readonly MessageService $messages,
        private readonly AttachmentService $attachments,
        private readonly LinkService $links,
        private readonly CommunicationAuthorizer $authorizer,
        private readonly ReadStateService $reads,
    ) {}

    /**
     * @return array{
     *     channel_key: string,
     *     linkable_type: string,
     *     include_linked_actor: bool,
     *     linked_actor_type: ?string,
     *     conversation_type: string,
     *     purpose: string
     * }
     */
    public function profile(string $profileKey): array
    {
        $profile = config('communication.record_threads.'.$profileKey);

        if (! is_array($profile) || ! isset($profile['channel_key'], $profile['linkable_type'])) {
            throw new InvalidArgumentException("Unknown record thread profile [{$profileKey}].");
        }

        return [
            'channel_key' => (string) $profile['channel_key'],
            'linkable_type' => (string) $profile['linkable_type'],
            'include_linked_actor' => (bool) ($profile['include_linked_actor'] ?? false),
            'linked_actor_type' => isset($profile['linked_actor_type']) && is_string($profile['linked_actor_type'])
                ? $profile['linked_actor_type']
                : null,
            'conversation_type' => (string) ($profile['conversation_type'] ?? Conversation::TYPE_GROUP),
            'purpose' => (string) ($profile['purpose'] ?? $profileKey.'_thread'),
        ];
    }

    public function ensureChannel(string $profileKey): void
    {
        $profile = $this->profile($profileKey);
        $definition = config('communication.default_channels.'.$profile['channel_key']);

        if (is_array($definition)) {
            $this->channels->syncDefinitions([$profile['channel_key'] => $definition]);
        }
    }

    public function conversationFor(User $user, string $profileKey, int $linkableId): Conversation
    {
        $profile = $this->profile($profileKey);
        $this->ensureChannel($profileKey);
        $this->authorizer->assertCanLinkRecord(
            ActorRef::user($user->id),
            $profile['linkable_type'],
            $linkableId,
        );

        $existing = $this->findThread($profileKey, $linkableId);

        if ($existing !== null) {
            $this->ensureParticipants($existing, $user, $profile, $linkableId);
            $this->reads->markRead($existing, ActorRef::user($user->id));

            return $existing->fresh(['channel', 'messages.attachments', 'messages.participant', 'links']) ?? $existing;
        }

        return DB::transaction(function () use ($user, $profileKey, $linkableId, $profile): Conversation {
            $label = Str::headline(str_replace('_', ' ', $profile['linkable_type']));
            $members = [];

            if ($profile['include_linked_actor'] && $profile['linked_actor_type'] !== null) {
                $members[] = new ActorRef($profile['linked_actor_type'], $linkableId);
            }

            $conversation = $this->conversations->create(
                $profile['channel_key'],
                ActorRef::user($user->id),
                $members,
                [
                    'subject' => __('communication.record_threads.subject', [
                        'record' => $label.' #'.$linkableId,
                    ]),
                    'type' => $this->resolveConversationType($profile, $members),
                    'meta' => [
                        'purpose' => $profile['purpose'],
                        'profile_key' => $profileKey,
                        'primary_linkable_type' => $profile['linkable_type'],
                        'primary_linkable_id' => $linkableId,
                    ],
                ],
            );

            $this->links->attach(
                $conversation,
                ActorRef::user($user->id),
                $profile['linkable_type'],
                $linkableId,
                ConversationLink::RELATION_ABOUT,
            );

            return $conversation->fresh(['channel', 'messages.attachments', 'messages.participant', 'links']) ?? $conversation;
        });
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return array{conversation: Conversation, message: Message, attachments: list<Attachment>}
     */
    public function postMessage(
        User $user,
        string $profileKey,
        int $linkableId,
        string $body,
        array $files = [],
    ): array {
        $body = trim($body);

        if ($body === '' && $files === []) {
            throw ValidationException::withMessages([
                'body' => [__('communication.record_threads.errors.empty')],
            ]);
        }

        $conversation = $this->conversationFor($user, $profileKey, $linkableId);
        $actor = ActorRef::user($user->id);
        $attachments = [];

        if ($files !== []) {
            $attachments = $this->attachments->postWithFiles(
                $conversation,
                $actor,
                $files,
                $body !== '' ? $body : null,
            );
            $message = Message::query()
                ->where('conversation_id', $conversation->id)
                ->latest('id')
                ->firstOrFail();
        } else {
            $message = $this->messages->postText($conversation, $actor, $body);
        }

        return [
            'conversation' => $conversation->fresh(['channel', 'messages.attachments', 'messages.participant', 'links']) ?? $conversation,
            'message' => $message->fresh(['attachments', 'participant']) ?? $message,
            'attachments' => $attachments,
        ];
    }

    /**
     * @return Collection<int, Message>
     */
    public function messagesFor(User $user, string $profileKey, int $linkableId): Collection
    {
        $conversation = $this->conversationFor($user, $profileKey, $linkableId);

        return Message::query()
            ->with(['attachments', 'participant'])
            ->where('conversation_id', $conversation->id)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();
    }

    public function findThread(string $profileKey, int $linkableId): ?Conversation
    {
        $profile = $this->profile($profileKey);
        $this->ensureChannel($profileKey);
        $channel = $this->channels->findByKey($profile['channel_key']);

        if ($channel === null) {
            return null;
        }

        return Conversation::query()
            ->where('channel_id', $channel->id)
            ->whereHas('links', function ($query) use ($profile, $linkableId): void {
                $query->where('linkable_type', $profile['linkable_type'])
                    ->where('linkable_id', $linkableId)
                    ->where('relation', ConversationLink::RELATION_ABOUT);
            })
            ->where(function ($query) use ($profile, $profileKey): void {
                $query->where('meta->purpose', $profile['purpose'])
                    ->orWhere('meta->profile_key', $profileKey);
            })
            ->latest('id')
            ->first();
    }

    /**
     * @param  array{
     *     channel_key: string,
     *     linkable_type: string,
     *     include_linked_actor: bool,
     *     linked_actor_type: ?string,
     *     conversation_type: string,
     *     purpose: string
     * }  $profile
     * @param  list<ActorRef>  $members
     */
    private function resolveConversationType(array $profile, array $members): string
    {
        $requested = $profile['conversation_type'];

        if ($requested === Conversation::TYPE_DIRECT && count($members) <= 1) {
            return Conversation::TYPE_DIRECT;
        }

        if ($requested === Conversation::TYPE_DIRECT && count($members) > 1) {
            return Conversation::TYPE_GROUP;
        }

        return in_array($requested, [
            Conversation::TYPE_DIRECT,
            Conversation::TYPE_GROUP,
            Conversation::TYPE_NOTES,
        ], true) ? $requested : Conversation::TYPE_GROUP;
    }

    /**
     * @param  array{
     *     channel_key: string,
     *     linkable_type: string,
     *     include_linked_actor: bool,
     *     linked_actor_type: ?string,
     *     conversation_type: string,
     *     purpose: string
     * }  $profile
     */
    private function ensureParticipants(Conversation $conversation, User $user, array $profile, int $linkableId): void
    {
        $this->participants->add($conversation, ActorRef::user($user->id), Participant::ROLE_MEMBER);

        if ($profile['include_linked_actor'] && $profile['linked_actor_type'] !== null) {
            $this->participants->add(
                $conversation,
                new ActorRef($profile['linked_actor_type'], $linkableId),
                Participant::ROLE_MEMBER,
            );
        }
    }
}
