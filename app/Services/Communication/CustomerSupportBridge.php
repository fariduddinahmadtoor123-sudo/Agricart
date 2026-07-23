<?php

namespace App\Services\Communication;

use App\Models\AgricartConversation;
use App\Models\AgricartMessage;
use App\Models\Communication\Conversation;
use App\Models\Communication\ConversationLink;
use App\Models\Communication\Message;
use App\Models\Communication\Participant;
use App\Models\Customer;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\Communication\Support\ActorRef;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Dual-writes Agricart Customer Inbox into the Communication Platform
 * (customer_support) without replacing legacy tables.
 */
class CustomerSupportBridge
{
    public const CHANNEL_KEY = 'customer_support';

    public function __construct(
        private readonly ChannelRegistry $channels,
        private readonly ConversationService $conversations,
        private readonly ParticipantService $participants,
        private readonly LinkService $links,
        private readonly ReadStateService $reads,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('communication.engine_enabled', true)
            && Schema::hasTable('comm_conversations');
    }

    public function mirrorAgricartMessage(
        AgricartConversation $legacy,
        AgricartMessage $legacyMessage,
    ): ?Message {
        if (! $this->enabled()) {
            return null;
        }

        $this->ensureChannel();

        $conversation = $this->findOrCreateConversation($legacy);
        $this->syncParticipants($conversation, $legacy, $legacyMessage);
        $this->syncRecordLinks($conversation, $legacy);

        $eventKey = 'legacy.agricart_message.'.$legacyMessage->id;
        $existing = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('event_key', $eventKey)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $participantId = null;
        $messageType = Message::TYPE_TEXT;

        if ($legacyMessage->sender_type === AgricartMessage::SENDER_SYSTEM || $legacyMessage->is_system) {
            $messageType = Message::TYPE_SYSTEM;
        } elseif ($legacyMessage->sender_type === AgricartMessage::SENDER_CUSTOMER && $legacyMessage->customer_id) {
            $participantId = Participant::query()
                ->where('conversation_id', $conversation->id)
                ->where('actor_type', 'customer')
                ->where('actor_id', $legacyMessage->customer_id)
                ->whereNull('left_at')
                ->value('id');
        } elseif ($legacyMessage->sender_type === AgricartMessage::SENDER_ADMIN && $legacyMessage->user_id) {
            $participantId = Participant::query()
                ->where('conversation_id', $conversation->id)
                ->where('actor_type', 'user')
                ->where('actor_id', $legacyMessage->user_id)
                ->whereNull('left_at')
                ->value('id');
        } elseif ($legacyMessage->sender_type === AgricartMessage::SENDER_CUSTOMER) {
            // Guest contact message — attribute to system participant.
            $participantId = Participant::query()
                ->where('conversation_id', $conversation->id)
                ->where('actor_type', 'system')
                ->whereNull('left_at')
                ->value('id');
        }

        $meta = is_array($legacyMessage->meta) ? $legacyMessage->meta : [];
        $meta['legacy_agricart_message_id'] = $legacyMessage->id;
        $meta['legacy_agricart_conversation_id'] = $legacy->id;
        $meta['legacy_sender_type'] = $legacyMessage->sender_type;

        if (filled($legacyMessage->event_key)) {
            $meta['legacy_event_key'] = $legacyMessage->event_key;
        }

        $message = Message::query()->create([
            'uuid' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'participant_id' => $participantId,
            'message_type' => $messageType,
            'body' => $legacyMessage->body,
            'body_format' => 'plain',
            'event_key' => $eventKey,
            'meta' => $meta,
        ]);

        $status = $legacy->status === AgricartConversation::STATUS_CLOSED
            ? Conversation::STATUS_CLOSED
            : Conversation::STATUS_OPEN;

        $conversation->forceFill([
            'last_message_at' => $legacy->last_message_at ?? $legacyMessage->created_at ?? now(),
            'last_message_id' => $message->id,
            'status' => $status,
            'subject' => $legacy->subject ?: $conversation->subject,
        ])->save();

        if ($legacyMessage->sender_type === AgricartMessage::SENDER_CUSTOMER && $legacyMessage->customer_id) {
            $this->reads->markRead($conversation, ActorRef::customer((int) $legacyMessage->customer_id), $message->id);
        }

        if ($legacyMessage->sender_type === AgricartMessage::SENDER_ADMIN && $legacyMessage->user_id) {
            $this->reads->markRead($conversation, ActorRef::user((int) $legacyMessage->user_id), $message->id);
        }

        return $message;
    }

    public function markReadByCustomer(AgricartConversation $legacy, Customer $customer): void
    {
        if (! $this->enabled()) {
            return;
        }

        $conversation = $this->findConversation($legacy);

        if ($conversation === null) {
            return;
        }

        $this->participants->add($conversation, ActorRef::customer($customer->id));
        $this->reads->markRead($conversation, ActorRef::customer($customer->id));
    }

    public function markReadByAdmin(AgricartConversation $legacy, ?User $admin = null): void
    {
        if (! $this->enabled()) {
            return;
        }

        $conversation = $this->findConversation($legacy);

        if ($conversation === null) {
            return;
        }

        if ($admin instanceof User) {
            $this->participants->add($conversation, ActorRef::user($admin->id));
            $this->reads->markRead($conversation, ActorRef::user($admin->id));

            return;
        }

        $userParticipants = Participant::query()
            ->where('conversation_id', $conversation->id)
            ->where('actor_type', 'user')
            ->whereNull('left_at')
            ->get();

        foreach ($userParticipants as $participant) {
            if ($participant->actor_id) {
                $this->reads->markRead($conversation, ActorRef::user((int) $participant->actor_id));
            }
        }
    }

    public function syncStatus(AgricartConversation $legacy): void
    {
        if (! $this->enabled()) {
            return;
        }

        $conversation = $this->findConversation($legacy);

        if ($conversation === null) {
            return;
        }

        $conversation->update([
            'status' => $legacy->status === AgricartConversation::STATUS_CLOSED
                ? Conversation::STATUS_CLOSED
                : Conversation::STATUS_OPEN,
            'subject' => $legacy->subject ?: $conversation->subject,
        ]);
    }

    public function findConversation(AgricartConversation $legacy): ?Conversation
    {
        if (! $this->enabled()) {
            return null;
        }

        return Conversation::query()
            ->where('meta->legacy_agricart_conversation_id', $legacy->id)
            ->first();
    }

    private function findOrCreateConversation(AgricartConversation $legacy): Conversation
    {
        $existing = $this->findConversation($legacy);

        if ($existing !== null) {
            return $existing;
        }

        $this->ensureChannel();

        $creator = $legacy->customer_id
            ? ActorRef::customer((int) $legacy->customer_id)
            : ActorRef::system();

        $type = Conversation::TYPE_DIRECT;

        return $this->conversations->create(
            self::CHANNEL_KEY,
            $creator,
            [],
            [
                'subject' => $legacy->subject,
                'type' => $type,
                'meta' => [
                    'legacy_agricart_conversation_id' => $legacy->id,
                    'legacy_topic' => $legacy->topic,
                    'guest_name' => $legacy->guest_name,
                    'guest_phone' => $legacy->guest_phone,
                    'guest_email' => $legacy->guest_email,
                    'source_url' => $legacy->source_url,
                    'store_inquiry_id' => $legacy->store_inquiry_id,
                ],
            ],
        );
    }

    private function syncParticipants(
        Conversation $conversation,
        AgricartConversation $legacy,
        AgricartMessage $legacyMessage,
    ): void {
        if ($legacy->customer_id) {
            $this->participants->add(
                $conversation,
                ActorRef::customer((int) $legacy->customer_id),
                Participant::ROLE_OWNER,
            );
        } else {
            $this->participants->add(
                $conversation,
                ActorRef::system(),
                Participant::ROLE_OWNER,
            );
        }

        if ($legacyMessage->user_id) {
            $this->participants->add(
                $conversation,
                ActorRef::user((int) $legacyMessage->user_id),
                Participant::ROLE_MEMBER,
            );
        }
    }

    private function syncRecordLinks(Conversation $conversation, AgricartConversation $legacy): void
    {
        if ($legacy->online_sale_order_id) {
            $this->safeLink($conversation, 'online_sale_order', (int) $legacy->online_sale_order_id);
        }

        if ($legacy->product_review_id) {
            $this->safeLink($conversation, 'product_review', (int) $legacy->product_review_id);

            $productId = ProductReview::query()
                ->whereKey($legacy->product_review_id)
                ->value('product_id');

            if ($productId) {
                $this->safeLink($conversation, 'product', (int) $productId);
            }
        }

        if ($legacy->customer_id) {
            $this->safeLink($conversation, 'customer', (int) $legacy->customer_id);
        }
    }

    private function safeLink(Conversation $conversation, string $type, int $id): void
    {
        $exists = ConversationLink::query()
            ->where('conversation_id', $conversation->id)
            ->where('linkable_type', $type)
            ->where('linkable_id', $id)
            ->exists();

        if ($exists || ! $this->channels->isRegisteredLinkableType($type)) {
            return;
        }

        ConversationLink::query()->firstOrCreate([
            'conversation_id' => $conversation->id,
            'linkable_type' => $type,
            'linkable_id' => $id,
            'relation' => ConversationLink::RELATION_ABOUT,
        ]);
    }

    private function ensureChannel(): void
    {
        $definitions = config('communication.default_channels', []);
        $definition = $definitions[self::CHANNEL_KEY] ?? null;

        if (is_array($definition)) {
            $this->channels->syncDefinitions([self::CHANNEL_KEY => $definition]);
        }
    }
}
