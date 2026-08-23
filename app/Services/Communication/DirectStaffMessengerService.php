<?php

namespace App\Services\Communication;

use App\Models\Communication\Conversation;
use App\Models\Communication\Message;
use App\Models\Communication\Participant;
use App\Models\User;
use App\Services\Communication\Support\ActorRef;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DirectStaffMessengerService
{
    public const CHANNEL_KEY = 'staff_ops';

    public const PURPOSE = 'direct_staff_dm';

    public function __construct(
        private readonly ChannelRegistry $channels,
        private readonly ConversationService $conversations,
        private readonly ParticipantService $participants,
        private readonly MessageService $messages,
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

    public function conversationWith(User $user, User $peer): Conversation
    {
        if ($user->id === $peer->id) {
            throw ValidationException::withMessages([
                'peer' => [__('communication.messenger.errors.self')],
            ]);
        }

        if (! $user->canUseAdmin()) {
            throw ValidationException::withMessages([
                'peer' => [__('communication.messenger.errors.forbidden')],
            ]);
        }

        if ($peer->status !== User::STATUS_ACTIVE || ! $peer->canUseAdmin()) {
            throw ValidationException::withMessages([
                'peer' => [__('communication.messenger.errors.peer')],
            ]);
        }

        $this->ensureChannel();
        $actor = ActorRef::user($user->id);
        $peerActor = ActorRef::user($peer->id);

        // Staff-to-staff DMs are available to any admin user (not sales-only).
        $pair = $this->pairKey($user->id, $peer->id);
        $existing = $this->findDm($pair);

        if ($existing !== null) {
            $this->participants->add($existing, $actor, Participant::ROLE_MEMBER);
            $this->participants->add($existing, $peerActor, Participant::ROLE_MEMBER);
            $this->reads->markRead($existing, $actor);

            return $existing->fresh(['channel', 'participants']) ?? $existing;
        }

        return DB::transaction(function () use ($user, $peer, $actor, $peerActor, $pair): Conversation {
            $conversation = $this->conversations->create(
                self::CHANNEL_KEY,
                $actor,
                [$peerActor],
                [
                    'subject' => __('communication.messenger.dm_subject', [
                        'a' => $user->name,
                        'b' => $peer->name,
                    ]),
                    'type' => Conversation::TYPE_DIRECT,
                    'meta' => [
                        'purpose' => self::PURPOSE,
                        'peer_pair' => $pair,
                    ],
                ],
            );

            return $conversation->fresh(['channel', 'participants']) ?? $conversation;
        });
    }

    public function postMessage(User $user, User $peer, string $body): Message
    {
        $body = trim($body);

        if ($body === '' || mb_strlen($body) > 5000) {
            throw ValidationException::withMessages([
                'body' => [__('communication.messenger.errors.body')],
            ]);
        }

        $conversation = $this->conversationWith($user, $peer);

        return $this->messages->postText(
            $conversation,
            ActorRef::user($user->id),
            $body,
        );
    }

    /**
     * @return Collection<int, Message>
     */
    public function messagesWith(User $user, User $peer): Collection
    {
        $conversation = $this->conversationWith($user, $peer);

        return Message::query()
            ->with('participant')
            ->where('conversation_id', $conversation->id)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->limit(200)
            ->get();
    }

    /**
     * Lightweight staff picker rows for messenger search (includes unread flags).
     *
     * @return list<array{id: int, name: string, email: string, online: bool, unread: int}>
     */
    public function searchableStaff(User $user, ?string $query = null, int $limit = 20): array
    {
        $onlineIds = $this->onlineUserIds();
        $unreadPeers = $this->unreadPeerMap($user);
        $needle = trim((string) $query);

        $staffQuery = User::query()
            ->where('status', User::STATUS_ACTIVE)
            ->whereNotNull('role_id')
            ->whereKeyNot($user->id)
            ->with('role')
            ->orderBy('name')
            ->limit(100);

        if ($needle !== '') {
            $like = '%'.addcslashes($needle, '%_\\').'%';
            $staffQuery->where(function ($builder) use ($like): void {
                $builder->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        return $staffQuery
            ->get(['id', 'name', 'email', 'role_id', 'status'])
            ->filter(fn (User $peer): bool => $peer->canUseAdmin())
            ->unique('id')
            ->map(fn (User $peer): array => [
                'id' => (int) $peer->id,
                'name' => (string) $peer->name,
                'email' => (string) $peer->email,
                'online' => $onlineIds->contains($peer->id),
                'unread' => isset($unreadPeers[(int) $peer->id]) ? 1 : 0,
            ])
            ->sortBy([
                fn (array $row): int => $row['unread'] > 0 ? 0 : 1,
                fn (array $row): int => $row['online'] ? 0 : 1,
                fn (array $row): string => mb_strtolower((string) $row['name']),
            ])
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Peer user ids that currently have an unread staff DM for this user.
     *
     * @return array<int, int> peerId => 1
     */
    public function unreadPeerMap(User $user): array
    {
        $this->ensureChannel();
        $channel = $this->channels->findByKey(self::CHANNEL_KEY);

        if ($channel === null) {
            return [];
        }

        $actor = ActorRef::user($user->id);
        $map = [];

        $conversations = Conversation::query()
            ->where('channel_id', $channel->id)
            ->where('meta->purpose', self::PURPOSE)
            ->whereHas('participants', function ($query) use ($user): void {
                $query->where('actor_type', 'user')
                    ->where('actor_id', $user->id)
                    ->whereNull('left_at');
            })
            ->limit(50)
            ->get(['id', 'last_message_id', 'meta']);

        foreach ($conversations as $conversation) {
            if (! $this->reads->isUnreadFor($conversation, $actor)) {
                continue;
            }

            $peerId = $this->peerIdFromPair(
                (string) data_get($conversation->meta, 'peer_pair', ''),
                (int) $user->id,
            );

            if ($peerId !== null) {
                $map[$peerId] = 1;
            }
        }

        return $map;
    }

    public function markExistingDmRead(User $user, Conversation $conversation): void
    {
        $this->reads->markRead($conversation, ActorRef::user($user->id));
    }

    /**
     * Peer read watermark for 1:1 threads (WhatsApp-style "seen").
     */
    public function peerLastReadMessageId(Conversation $conversation, User $viewer): ?int
    {
        $peer = $conversation->participants()
            ->whereNull('left_at')
            ->where('actor_type', 'user')
            ->where('actor_id', '!=', $viewer->id)
            ->first();

        if ($peer === null || $peer->last_read_message_id === null) {
            return null;
        }

        return (int) $peer->last_read_message_id;
    }

    /**
     * @return array{peer_last_read_message_id: ?int}
     */
    public function dmReceipts(Conversation $conversation, User $viewer): array
    {
        return [
            'peer_last_read_message_id' => $this->peerLastReadMessageId($conversation, $viewer),
        ];
    }

    public function peerIdFromPair(string $pairKey, int $userId): ?int
    {
        $parts = explode(':', $pairKey);

        if (count($parts) !== 2) {
            return null;
        }

        $a = (int) $parts[0];
        $b = (int) $parts[1];

        if ($a === $userId) {
            return $b > 0 ? $b : null;
        }

        if ($b === $userId) {
            return $a > 0 ? $a : null;
        }

        return null;
    }

    /**
     * @return Collection<int, array{user: User, online: bool, unread: int, last_body: ?string, last_at: ?string}>
     */
    public function directory(User $user): Collection
    {
        $this->ensureChannel();
        $onlineIds = $this->onlineUserIds();

        $staff = User::query()
            ->where('status', User::STATUS_ACTIVE)
            ->whereNotNull('role_id')
            ->whereKeyNot($user->id)
            ->with('role')
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'email', 'role_id', 'status']);

        return $staff
            ->filter(fn (User $peer): bool => $peer->status === User::STATUS_ACTIVE && $peer->canUseAdmin())
            ->unique('id')
            ->map(function (User $peer) use ($user, $onlineIds): array {
                $pair = $this->pairKey($user->id, $peer->id);
                $conversation = $this->findDm($pair);
                $unread = 0;
                $lastBody = null;
                $lastAt = null;

                if ($conversation !== null) {
                    $unread = $this->reads->isUnreadFor($conversation, ActorRef::user($user->id)) ? 1 : 0;
                    $last = Message::query()
                        ->where('conversation_id', $conversation->id)
                        ->whereNull('deleted_at')
                        ->latest('id')
                        ->first();
                    $lastBody = $last?->body;
                    $lastAt = $last?->created_at?->toIso8601String();
                }

                return [
                    'user' => $peer,
                    'online' => $onlineIds->contains($peer->id),
                    'unread' => $unread,
                    'last_body' => $lastBody,
                    'last_at' => $lastAt,
                ];
            })->sortBy([
                fn (array $row): int => $row['unread'] > 0 ? 0 : 1,
                fn (array $row): int => $row['online'] ? 0 : 1,
                fn (array $row): string => mb_strtolower((string) $row['user']->name),
            ])->values();
    }

    public function totalUnread(User $user): int
    {
        return count($this->unreadPeerMap($user));
    }

    public function findDm(string $pairKey): ?Conversation
    {
        $this->ensureChannel();
        $channel = $this->channels->findByKey(self::CHANNEL_KEY);

        if ($channel === null) {
            return null;
        }

        return Conversation::query()
            ->where('channel_id', $channel->id)
            ->where('meta->purpose', self::PURPOSE)
            ->where('meta->peer_pair', $pairKey)
            ->latest('id')
            ->first();
    }

    public function pairKey(int $a, int $b): string
    {
        $ids = [$a, $b];
        sort($ids);

        return $ids[0].':'.$ids[1];
    }

    /**
     * @return Collection<int, int>
     */
    public function onlineUserIds(): Collection
    {
        if (! Schema::hasTable('sessions')) {
            return collect();
        }

        $since = now()->subMinutes(5)->getTimestamp();

        return DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $since)
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }
}
