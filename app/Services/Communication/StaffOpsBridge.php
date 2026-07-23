<?php

namespace App\Services\Communication;

use App\Models\Communication\Conversation;
use App\Models\Communication\ConversationLink;
use App\Models\Communication\Message;
use App\Models\Communication\Participant;
use App\Models\OnlineSaleOrder;
use App\Models\StaffMessage;
use App\Models\StaffMessageThread;
use App\Models\User;
use App\Services\Communication\Support\ActorRef;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Dual-writes Staff Inbox activity into the Communication Platform (staff_ops)
 * without replacing the legacy tables. Legacy remains the operational UI source
 * until a later cutover phase.
 */
class StaffOpsBridge
{
    public const CHANNEL_KEY = 'staff_ops';

    public function __construct(
        private readonly ChannelRegistry $channels,
        private readonly ConversationService $conversations,
        private readonly ParticipantService $participants,
        private readonly MessageService $messages,
        private readonly LinkService $links,
        private readonly ReadStateService $reads,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('communication.engine_enabled', true)
            && Schema::hasTable('comm_conversations');
    }

    public function syncThreadParticipants(StaffMessageThread $thread, User ...$users): Conversation
    {
        $this->ensureChannel();

        $conversation = $this->findOrCreateConversation($thread, $users[0] ?? null);

        foreach ($users as $user) {
            $this->participants->add(
                $conversation,
                ActorRef::user($user->id),
                $this->roleFor($thread, $user),
            );
        }

        return $conversation->fresh(['participants', 'channel']) ?? $conversation;
    }

    public function mirrorStaffMessage(
        StaffMessageThread $thread,
        StaffMessage $staffMessage,
        User $sender,
        bool $markSenderRead = true,
    ): ?Message {
        if (! $this->enabled()) {
            return null;
        }

        $this->ensureChannel();

        $peerUsers = $thread->participants
            ->map(fn ($row) => $row->user)
            ->filter(fn ($user): bool => $user instanceof User)
            ->unique('id')
            ->values()
            ->all();

        $conversation = $this->syncThreadParticipants($thread, $sender, ...$peerUsers);

        if ($thread->context_type === StaffMessageThread::CONTEXT_ONLINE_SALE_ORDER
            && $thread->context_id) {
            $this->ensureOrderLink($conversation, (int) $thread->context_id, $sender);
        }

        $eventKey = 'legacy.staff_message.'.$staffMessage->id;
        $existing = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('event_key', $eventKey)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $meta = is_array($staffMessage->meta) ? $staffMessage->meta : [];
        $messageType = ($meta['type'] ?? null) === 'order_forward'
            ? Message::TYPE_FORWARD
            : Message::TYPE_TEXT;

        $participant = Participant::query()
            ->where('conversation_id', $conversation->id)
            ->where('actor_type', 'user')
            ->where('actor_id', $sender->id)
            ->whereNull('left_at')
            ->first();

        $message = Message::query()->create([
            'uuid' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'participant_id' => $participant?->id,
            'message_type' => $messageType,
            'body' => $staffMessage->body,
            'body_format' => 'plain',
            'event_key' => $eventKey,
            'meta' => array_merge($meta, [
                'legacy_staff_message_id' => $staffMessage->id,
                'legacy_staff_thread_id' => $thread->id,
            ]),
        ]);

        $conversation->forceFill([
            'last_message_at' => $staffMessage->created_at ?? now(),
            'last_message_id' => $message->id,
            'status' => Conversation::STATUS_OPEN,
            'subject' => $thread->subject ?: $conversation->subject,
        ])->save();

        if ($markSenderRead && $participant !== null) {
            $this->reads->markRead($conversation, ActorRef::user($sender->id), $message->id);
        }

        return $message;
    }

    public function markReadFromLegacy(StaffMessageThread $thread, User $user): void
    {
        if (! $this->enabled()) {
            return;
        }

        $conversation = $this->findConversation($thread);

        if ($conversation === null) {
            return;
        }

        $this->participants->add($conversation, ActorRef::user($user->id));
        $this->reads->markRead($conversation, ActorRef::user($user->id));
    }

    public function findConversation(StaffMessageThread $thread): ?Conversation
    {
        if (! $this->enabled()) {
            return null;
        }

        return Conversation::query()
            ->where('meta->legacy_staff_thread_id', $thread->id)
            ->first();
    }

    private function findOrCreateConversation(StaffMessageThread $thread, ?User $creator): Conversation
    {
        $existing = $this->findConversation($thread);

        if ($existing !== null) {
            return $existing;
        }

        $channel = $this->channels->requireByKey(self::CHANNEL_KEY);
        $creator ??= User::query()->find($thread->created_by_id) ?? User::query()->first();

        if ($creator === null) {
            throw new \RuntimeException('Cannot bridge staff thread without a creator user.');
        }

        $conversation = $this->conversations->create(
            self::CHANNEL_KEY,
            ActorRef::user($creator->id),
            [],
            [
                'subject' => $thread->subject,
                'type' => Conversation::TYPE_GROUP,
                'meta' => [
                    'legacy_staff_thread_id' => $thread->id,
                    'legacy_context_type' => $thread->context_type,
                    'legacy_context_id' => $thread->context_id,
                ],
            ],
        );

        return $conversation;
    }

    private function ensureOrderLink(Conversation $conversation, int $orderId, User $actor): void
    {
        $exists = ConversationLink::query()
            ->where('conversation_id', $conversation->id)
            ->where('linkable_type', 'online_sale_order')
            ->where('linkable_id', $orderId)
            ->exists();

        if ($exists) {
            return;
        }

        if (! OnlineSaleOrder::query()->whereKey($orderId)->exists()) {
            return;
        }

        try {
            $this->links->attach(
                $conversation,
                ActorRef::user($actor->id),
                'online_sale_order',
                $orderId,
                ConversationLink::RELATION_ABOUT,
            );
        } catch (\Throwable) {
            // Linking must not block order forward; conversation still mirrors.
            ConversationLink::query()->firstOrCreate([
                'conversation_id' => $conversation->id,
                'linkable_type' => 'online_sale_order',
                'linkable_id' => $orderId,
                'relation' => ConversationLink::RELATION_ABOUT,
            ]);
        }
    }

    private function ensureChannel(): void
    {
        $definitions = config('communication.default_channels', []);
        $staffOps = $definitions[self::CHANNEL_KEY] ?? null;

        if (is_array($staffOps)) {
            $this->channels->syncDefinitions([self::CHANNEL_KEY => $staffOps]);
        }
    }

    private function roleFor(StaffMessageThread $thread, User $user): string
    {
        return (int) $thread->created_by_id === (int) $user->id
            ? Participant::ROLE_OWNER
            : Participant::ROLE_MEMBER;
    }
}
