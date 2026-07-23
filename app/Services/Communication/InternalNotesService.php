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

class InternalNotesService
{
    public const CHANNEL_KEY = 'internal_notes';

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

    public function ensureChannel(): void
    {
        $definition = config('communication.default_channels.'.self::CHANNEL_KEY);

        if (is_array($definition)) {
            $this->channels->syncDefinitions([self::CHANNEL_KEY => $definition]);
        }
    }

    /**
     * Find or create the primary internal-notes conversation for a record.
     */
    public function conversationForRecord(User $user, string $linkableType, int $linkableId): Conversation
    {
        $this->ensureChannel();
        $this->authorizer->assertCanLinkRecord(ActorRef::user($user->id), $linkableType, $linkableId);

        $existing = $this->findNotesConversation($linkableType, $linkableId);

        if ($existing !== null) {
            $this->participants->add($existing, ActorRef::user($user->id), Participant::ROLE_MEMBER);
            $this->reads->markRead($existing, ActorRef::user($user->id));

            return $existing->fresh(['channel', 'messages.attachments', 'messages.participant', 'links']) ?? $existing;
        }

        return DB::transaction(function () use ($user, $linkableType, $linkableId): Conversation {
            $label = Str::headline(str_replace('_', ' ', $linkableType));

            $conversation = $this->conversations->create(
                self::CHANNEL_KEY,
                ActorRef::user($user->id),
                [],
                [
                    'subject' => __('communication.internal_notes.subject', [
                        'record' => $label.' #'.$linkableId,
                    ]),
                    'type' => Conversation::TYPE_NOTES,
                    'meta' => [
                        'purpose' => 'internal_notes',
                        'primary_linkable_type' => $linkableType,
                        'primary_linkable_id' => $linkableId,
                    ],
                ],
            );

            $this->links->attach(
                $conversation,
                ActorRef::user($user->id),
                $linkableType,
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
    public function postNote(
        User $user,
        string $linkableType,
        int $linkableId,
        string $body,
        array $files = [],
    ): array {
        $body = trim($body);

        if ($body === '' && $files === []) {
            throw ValidationException::withMessages([
                'body' => [__('communication.internal_notes.errors.empty')],
            ]);
        }

        $conversation = $this->conversationForRecord($user, $linkableType, $linkableId);
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
    public function messagesForRecord(User $user, string $linkableType, int $linkableId): Collection
    {
        $conversation = $this->conversationForRecord($user, $linkableType, $linkableId);

        return Message::query()
            ->with(['attachments', 'participant'])
            ->where('conversation_id', $conversation->id)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();
    }

    public function findNotesConversation(string $linkableType, int $linkableId): ?Conversation
    {
        $this->ensureChannel();
        $channel = $this->channels->findByKey(self::CHANNEL_KEY);

        if ($channel === null) {
            return null;
        }

        return Conversation::query()
            ->where('channel_id', $channel->id)
            ->where('type', Conversation::TYPE_NOTES)
            ->whereHas('links', function ($query) use ($linkableType, $linkableId): void {
                $query->where('linkable_type', $linkableType)
                    ->where('linkable_id', $linkableId)
                    ->where('relation', ConversationLink::RELATION_ABOUT);
            })
            ->latest('id')
            ->first();
    }
}
