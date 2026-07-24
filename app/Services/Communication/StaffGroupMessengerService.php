<?php

namespace App\Services\Communication;

use App\Models\Communication\Conversation;
use App\Models\Communication\Message;
use App\Models\User;
use App\Services\Communication\Support\ActorRef;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StaffGroupMessengerService
{
    public const CHANNEL_KEY = DirectStaffMessengerService::CHANNEL_KEY;

    public const PURPOSE = 'staff_group_chat';

    public const MIN_OTHER_MEMBERS = 2;

    public const MAX_OTHER_MEMBERS = 5;

    public function __construct(
        private readonly ChannelRegistry $channels,
        private readonly ConversationService $conversations,
        private readonly MessageService $messages,
        private readonly CommunicationAuthorizer $authorizer,
        private readonly ReadStateService $reads,
        private readonly DirectStaffMessengerService $direct,
    ) {}

    /**
     * @return list<array{id: int, title: string, unread: int, member_count: int, members: list<string>}>
     */
    public function listGroups(User $user): array
    {
        $this->direct->ensureChannel();
        $channel = $this->channels->findByKey(self::CHANNEL_KEY);

        if ($channel === null) {
            return [];
        }

        $actor = ActorRef::user($user->id);

        $conversations = Conversation::query()
            ->where('channel_id', $channel->id)
            ->where('type', Conversation::TYPE_GROUP)
            ->where('meta->purpose', self::PURPOSE)
            ->whereHas('participants', function ($query) use ($user): void {
                $query->where('actor_type', 'user')
                    ->where('actor_id', $user->id)
                    ->whereNull('left_at');
            })
            ->with(['participants' => fn ($q) => $q->whereNull('left_at')])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        $memberIds = $conversations
            ->flatMap(fn (Conversation $c) => $c->participants->pluck('actor_id'))
            ->unique()
            ->values()
            ->all();

        $names = User::query()
            ->whereIn('id', $memberIds)
            ->pluck('name', 'id');

        return $conversations
            ->map(function (Conversation $conversation) use ($actor, $names, $user): array {
                $memberNames = $conversation->participants
                    ->filter(fn ($p) => $p->actor_type === 'user')
                    ->map(fn ($p) => (string) ($names[(int) $p->actor_id] ?? ''))
                    ->filter(fn (string $name): bool => $name !== '')
                    ->values()
                    ->all();

                $title = trim((string) ($conversation->subject ?: ''));
                if ($title === '') {
                    $title = collect($memberNames)
                        ->reject(fn (string $name): bool => $name === (string) $user->name)
                        ->take(3)
                        ->implode(', ');
                }

                return [
                    'id' => (int) $conversation->id,
                    'title' => $title !== '' ? $title : __('communication.messenger.group_fallback'),
                    'unread' => $this->reads->isUnreadFor($conversation, $actor) ? 1 : 0,
                    'member_count' => count($memberNames),
                    'members' => array_slice($memberNames, 0, 4),
                ];
            })
            ->sortBy([
                fn (array $row): int => $row['unread'] > 0 ? 0 : 1,
                fn (array $row): string => mb_strtolower((string) $row['title']),
            ])
            ->values()
            ->all();
    }

    public function totalUnread(User $user): int
    {
        $this->direct->ensureChannel();
        $channel = $this->channels->findByKey(self::CHANNEL_KEY);

        if ($channel === null) {
            return 0;
        }

        $actor = ActorRef::user($user->id);
        $total = 0;

        $conversations = Conversation::query()
            ->where('channel_id', $channel->id)
            ->where('type', Conversation::TYPE_GROUP)
            ->where('meta->purpose', self::PURPOSE)
            ->whereHas('participants', function ($query) use ($user): void {
                $query->where('actor_type', 'user')
                    ->where('actor_id', $user->id)
                    ->whereNull('left_at');
            })
            ->limit(40)
            ->get(['id', 'last_message_id']);

        foreach ($conversations as $conversation) {
            if ($this->reads->isUnreadFor($conversation, $actor)) {
                $total++;
            }
        }

        return $total;
    }

    /**
     * @param  list<int>  $memberIds
     */
    public function createGroup(User $user, string $title, array $memberIds): Conversation
    {
        if (! $user->canUseAdmin()) {
            throw ValidationException::withMessages([
                'members' => [__('communication.messenger.errors.forbidden')],
            ]);
        }

        $title = trim($title);
        if ($title === '' || mb_strlen($title) > 80) {
            throw ValidationException::withMessages([
                'title' => [__('communication.messenger.errors.group_title')],
            ]);
        }

        $ids = collect($memberIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id): bool => $id > 0 && $id !== (int) $user->id)
            ->unique()
            ->values();

        if ($ids->count() < self::MIN_OTHER_MEMBERS || $ids->count() > self::MAX_OTHER_MEMBERS) {
            throw ValidationException::withMessages([
                'members' => [__('communication.messenger.errors.group_members')],
            ]);
        }

        $peers = User::query()
            ->whereIn('id', $ids->all())
            ->where('status', User::STATUS_ACTIVE)
            ->whereNotNull('role_id')
            ->with('role')
            ->get();

        $valid = $peers->filter(fn (User $peer): bool => $peer->canUseAdmin());

        if ($valid->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'members' => [__('communication.messenger.errors.peer')],
            ]);
        }

        $this->direct->ensureChannel();
        $actor = ActorRef::user($user->id);
        $members = $valid
            ->map(fn (User $peer): ActorRef => ActorRef::user($peer->id))
            ->values()
            ->all();

        return $this->conversations->create(
            self::CHANNEL_KEY,
            $actor,
            $members,
            [
                'subject' => $title,
                'type' => Conversation::TYPE_GROUP,
                'meta' => [
                    'purpose' => self::PURPOSE,
                ],
            ],
        );
    }

    public function findGroupFor(User $user, int $conversationId): ?Conversation
    {
        $this->direct->ensureChannel();
        $channel = $this->channels->findByKey(self::CHANNEL_KEY);

        if ($channel === null) {
            return null;
        }

        $conversation = Conversation::query()
            ->whereKey($conversationId)
            ->where('channel_id', $channel->id)
            ->where('type', Conversation::TYPE_GROUP)
            ->where('meta->purpose', self::PURPOSE)
            ->first();

        if ($conversation === null) {
            return null;
        }

        $participant = $this->authorizer->activeParticipant(
            $conversation,
            ActorRef::user($user->id),
        );

        return $participant !== null ? $conversation : null;
    }

    /**
     * @return list<array{id: int, body: string, mine: bool, sender: ?string, time: ?string}>
     */
    public function messagesFor(User $user, Conversation $conversation, bool $markRead = true): array
    {
        $actor = ActorRef::user($user->id);

        $rows = Message::query()
            ->with('participant')
            ->where('conversation_id', $conversation->id)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->limit(200)
            ->get();

        $senderIds = $rows
            ->map(fn (Message $message) => $message->participant?->actor_type === 'user'
                ? (int) $message->participant->actor_id
                : null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $names = User::query()->whereIn('id', $senderIds)->pluck('name', 'id');

        if ($markRead) {
            $this->reads->markRead($conversation, $actor);
        }

        return $rows
            ->map(function (Message $message) use ($user, $names): array {
                $mine = $message->participant
                    && $message->participant->actor_type === 'user'
                    && (int) $message->participant->actor_id === (int) $user->id;

                $senderId = $message->participant?->actor_type === 'user'
                    ? (int) $message->participant->actor_id
                    : null;

                return [
                    'id' => (int) $message->id,
                    'body' => (string) $message->body,
                    'mine' => $mine,
                    'sender' => $mine ? null : (string) ($names[$senderId] ?? __('communication.messenger.staff')),
                    'time' => $message->created_at?->timezone(config('app.timezone'))->format('H:i'),
                ];
            })
            ->values()
            ->all();
    }

    public function postMessage(User $user, Conversation $conversation, string $body): Message
    {
        $body = trim($body);

        if ($body === '' || mb_strlen($body) > 5000) {
            throw ValidationException::withMessages([
                'body' => [__('communication.messenger.errors.body')],
            ]);
        }

        return $this->messages->postText(
            $conversation,
            ActorRef::user($user->id),
            $body,
        );
    }

    /**
     * @return Collection<int, string>
     */
    public function memberNames(Conversation $conversation): Collection
    {
        $ids = $conversation->participants()
            ->whereNull('left_at')
            ->where('actor_type', 'user')
            ->pluck('actor_id');

        return User::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->pluck('name');
    }
}
