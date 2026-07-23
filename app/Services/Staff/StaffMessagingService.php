<?php

namespace App\Services\Staff;

use App\Models\OnlineSaleOrder;
use App\Models\StaffMessage;
use App\Models\StaffMessageThread;
use App\Models\StaffMessageThreadParticipant;
use App\Models\User;
use App\Services\Communication\StaffOpsBridge;
use App\Services\Sales\OnlineOrderDispatchService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffMessagingService
{
    public function __construct(
        private readonly OnlineOrderDispatchService $dispatch,
        private readonly StaffOpsBridge $staffOpsBridge,
    ) {}

    public function forwardOrderToStaff(
        OnlineSaleOrder $order,
        User $recipient,
        User $sender,
        string $document = 'invoice',
    ): StaffMessageThread {
        if ($recipient->status !== User::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'recipient' => [__('staff_inbox.validation.recipient_inactive')],
            ]);
        }

        $document = $document === 'packing' ? 'packing' : 'invoice';

        return DB::transaction(function () use ($order, $recipient, $sender, $document): StaffMessageThread {
            $assignment = $this->dispatch->ensureAndMarkStaffSent($order, $recipient, $document, $sender);

            $thread = $this->findOrCreateOrderThread($order, $recipient, $sender);
            $documentUrl = $this->dispatch->documentUrl($order, $document, null, $recipient->id);
            $documentLabel = $document === 'packing'
                ? __('online_sale_orders.forward.document_packing')
                : __('online_sale_orders.forward.document_invoice');

            $body = __('staff_inbox.messages.order_forward_body', [
                'sender' => $sender->name,
                'order' => $order->order_number,
                'document' => $documentLabel,
            ]);

            // Self-forward must stay unread so the inbox badge lights up for the sender.
            $this->postMessage($thread, $sender, $body, [
                'type' => 'order_forward',
                'online_sale_order_id' => $order->id,
                'order_number' => $order->order_number,
                'document' => $document,
                'document_url' => $documentUrl,
                'staff_assignment_id' => $assignment->id,
            ], markSenderRead: $recipient->id !== $sender->id);

            $this->bumpUnreadCache();

            return $thread->fresh(['messages.sender', 'participants.user', 'latestMessage']) ?? $thread;
        });
    }

    public function postMessage(
        StaffMessageThread $thread,
        User $sender,
        string $body,
        array $meta = [],
        bool $markSenderRead = true,
    ): StaffMessage {
        $trimmed = trim($body);

        if ($trimmed === '') {
            throw ValidationException::withMessages([
                'body' => [__('staff_inbox.validation.body_required')],
            ]);
        }

        $this->ensureParticipant($thread, $sender);

        $message = StaffMessage::query()->create([
            'thread_id' => $thread->id,
            'sender_id' => $sender->id,
            'body' => $trimmed,
            'meta' => $meta === [] ? null : $meta,
        ]);

        $now = now();
        $thread->forceFill(['last_message_at' => $now])->save();

        if ($markSenderRead) {
            StaffMessageThreadParticipant::query()
                ->where('thread_id', $thread->id)
                ->where('user_id', $sender->id)
                ->update(['last_read_at' => $now]);
        }

        $thread->loadMissing(['participants.user']);
        $this->staffOpsBridge->mirrorStaffMessage($thread, $message, $sender, $markSenderRead);

        $this->bumpUnreadCache();

        return $message->fresh(['sender']) ?? $message;
    }

    public function markThreadRead(StaffMessageThread $thread, User $user): void
    {
        $this->ensureParticipant($thread, $user);

        StaffMessageThreadParticipant::query()
            ->where('thread_id', $thread->id)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        $this->staffOpsBridge->markReadFromLegacy($thread, $user);

        $this->bumpUnreadCache();
    }

    public function unreadCountFor(User $user): int
    {
        $version = (int) Cache::get('staff-inbox:unread:version', 1);

        return (int) Cache::remember(
            "staff-inbox:unread:{$version}:{$user->id}",
            now()->addSeconds(15),
            function () use ($user): int {
                return StaffMessageThreadParticipant::query()
                    ->join('staff_message_threads', 'staff_message_threads.id', '=', 'staff_message_thread_participants.thread_id')
                    ->where('staff_message_thread_participants.user_id', $user->id)
                    ->whereNotNull('staff_message_threads.last_message_at')
                    ->where(function ($query): void {
                        $query->whereNull('staff_message_thread_participants.last_read_at')
                            ->orWhereColumn(
                                'staff_message_thread_participants.last_read_at',
                                '<',
                                'staff_message_threads.last_message_at',
                            );
                    })
                    ->count();
            },
        );
    }

    public static function invalidateUnreadCache(): void
    {
        if (! Cache::increment('staff-inbox:unread:version')) {
            Cache::forever('staff-inbox:unread:version', 2);
        }
    }

    private function bumpUnreadCache(): void
    {
        self::invalidateUnreadCache();
    }

    private function findOrCreateOrderThread(
        OnlineSaleOrder $order,
        User $recipient,
        User $sender,
    ): StaffMessageThread {
        $existing = StaffMessageThread::query()
            ->where('context_type', StaffMessageThread::CONTEXT_ONLINE_SALE_ORDER)
            ->where('context_id', $order->id)
            ->whereHas('participants', fn ($query) => $query->where('user_id', $recipient->id))
            ->first();

        if ($existing !== null) {
            $this->ensureParticipant($existing, $sender);
            $this->ensureParticipant($existing, $recipient);

            return $existing;
        }

        $thread = StaffMessageThread::query()->create([
            'subject' => __('staff_inbox.messages.order_forward_subject', [
                'order' => $order->order_number,
            ]),
            'context_type' => StaffMessageThread::CONTEXT_ONLINE_SALE_ORDER,
            'context_id' => $order->id,
            'created_by_id' => $sender->id,
            'last_message_at' => null,
        ]);

        $this->ensureParticipant($thread, $sender);
        $this->ensureParticipant($thread, $recipient);

        return $thread;
    }

    private function ensureParticipant(StaffMessageThread $thread, User $user): void
    {
        StaffMessageThreadParticipant::query()->firstOrCreate(
            [
                'thread_id' => $thread->id,
                'user_id' => $user->id,
            ],
            [
                'last_read_at' => null,
            ],
        );
    }
}
