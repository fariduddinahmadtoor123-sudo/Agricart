<?php

namespace App\Services\Store;

use App\Models\AgricartConversation;
use App\Models\AgricartConversationRead;
use App\Models\AgricartMessage;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\Communication\CustomerSupportBridge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AgricartInboxService
{
    public function __construct(
        private readonly CustomerSupportBridge $customerSupportBridge,
    ) {}

    public function unreadCountForCustomer(Customer $customer): int
    {
        if (! Schema::hasTable('agricart_conversations')) {
            return 0;
        }

        return AgricartConversation::query()
            ->where('customer_id', $customer->id)
            ->whereNotNull('last_message_at')
            ->where(function ($query): void {
                $query->whereNull('customer_last_read_at')
                    ->orWhereColumn('customer_last_read_at', '<', 'last_message_at');
            })
            ->count();
    }

    public function unreadCountForAdmin(?User $user = null): int
    {
        if (! Schema::hasTable('agricart_conversations')) {
            return 0;
        }

        $user ??= auth()->user() instanceof User ? auth()->user() : null;
        $query = AgricartConversation::query()
            ->where('status', AgricartConversation::STATUS_OPEN)
            ->whereNotNull('last_message_at');

        $this->constrainWaitingOnCustomer($query);

        $hasAssignment = Schema::hasColumn('agricart_conversations', 'assigned_to_user_id');
        $hasStaffReads = Schema::hasTable('agricart_conversation_reads');

        if ($user instanceof User && $hasStaffReads) {
            if ($hasAssignment) {
                $query->where(function ($inner) use ($user): void {
                    $inner->whereNull('assigned_to_user_id')
                        ->orWhere('assigned_to_user_id', $user->id);
                });
            }

            $query->where(function ($inner) use ($user): void {
                $inner->whereDoesntHave('staffReads', function ($reads) use ($user): void {
                    $reads->where('user_id', $user->id)
                        ->whereColumn('agricart_conversation_reads.last_read_at', '>=', 'agricart_conversations.last_message_at');
                })->where(function ($legacy): void {
                    $legacy->whereNull('admin_last_read_at')
                        ->orWhereColumn('admin_last_read_at', '<', 'last_message_at');
                });
            });
        } else {
            $query->where(function ($inner): void {
                $inner->whereNull('admin_last_read_at')
                    ->orWhereColumn('admin_last_read_at', '<', 'last_message_at');
            });
        }

        return $query->count();
    }

    public function unassignedOpenCount(): int
    {
        if (! Schema::hasTable('agricart_conversations')
            || ! Schema::hasColumn('agricart_conversations', 'assigned_to_user_id')) {
            return 0;
        }

        return AgricartConversation::query()
            ->where('status', AgricartConversation::STATUS_OPEN)
            ->whereNull('assigned_to_user_id')
            ->count();
    }

    public function mineOpenCount(User $user): int
    {
        if (! Schema::hasTable('agricart_conversations')
            || ! Schema::hasColumn('agricart_conversations', 'assigned_to_user_id')) {
            return 0;
        }

        return AgricartConversation::query()
            ->where('status', AgricartConversation::STATUS_OPEN)
            ->where('assigned_to_user_id', $user->id)
            ->count();
    }

    public function assignTo(AgricartConversation $conversation, User $user): void
    {
        $conversation->update(['assigned_to_user_id' => $user->id]);
        $this->markStaffRead($conversation, $user);
    }

    /**
     * @param  array{name: string, phone: string, email?: string|null, message: string, source_url?: string|null}  $payload
     */
    public function startContactConversation(?Customer $customer, array $payload): AgricartConversation
    {
        $body = trim($payload['message']);

        if ($body === '') {
            throw ValidationException::withMessages([
                'message' => [__('agricart.inbox.errors.body')],
            ]);
        }

        return DB::transaction(function () use ($customer, $payload, $body): AgricartConversation {
            $conversation = AgricartConversation::query()->create([
                'customer_id' => $customer?->id,
                'subject' => __('agricart.inbox.subjects.contact'),
                'topic' => AgricartConversation::TOPIC_CONTACT,
                'status' => AgricartConversation::STATUS_OPEN,
                'guest_name' => $customer === null ? trim($payload['name']) : null,
                'guest_phone' => $customer === null ? trim($payload['phone']) : null,
                'guest_email' => $customer === null && filled($payload['email'] ?? null)
                    ? strtolower(trim((string) $payload['email']))
                    : null,
                'source_url' => $payload['source_url'] ?? null,
                'customer_last_read_at' => $customer !== null ? now() : null,
            ]);

            $this->appendMessage(
                $conversation,
                AgricartMessage::SENDER_CUSTOMER,
                $body,
                customer: $customer,
            );

            if ($customer !== null) {
                $conversation->update(['customer_last_read_at' => $this->readAt($conversation)]);
            }

            return $conversation->fresh(['messages']) ?? $conversation;
        });
    }

    public function startGeneralConversation(Customer $customer, string $subject, string $body): AgricartConversation
    {
        $subject = trim($subject);
        $body = trim($body);

        if ($subject === '' || mb_strlen($subject) > 200) {
            throw ValidationException::withMessages([
                'subject' => [__('agricart.inbox.errors.subject')],
            ]);
        }

        if ($body === '' || mb_strlen($body) < 2 || mb_strlen($body) > 5000) {
            throw ValidationException::withMessages([
                'body' => [__('agricart.inbox.errors.body')],
            ]);
        }

        return DB::transaction(function () use ($customer, $subject, $body): AgricartConversation {
            $conversation = AgricartConversation::query()->create([
                'customer_id' => $customer->id,
                'subject' => $subject,
                'topic' => AgricartConversation::TOPIC_GENERAL,
                'status' => AgricartConversation::STATUS_OPEN,
                'customer_last_read_at' => now(),
            ]);

            $this->appendMessage(
                $conversation,
                AgricartMessage::SENDER_CUSTOMER,
                $body,
                customer: $customer,
            );

            $conversation->update(['customer_last_read_at' => $this->readAt($conversation)]);

            return $conversation->fresh(['messages']) ?? $conversation;
        });
    }

    /**
     * Admin-initiated message (e.g. welcome) — appears in the customer's store Inbox.
     */
    public function startAdminMessage(Customer $customer, User $admin, string $subject, string $body): AgricartConversation
    {
        $subject = trim($subject);
        $body = trim($body);

        if ($subject === '' || mb_strlen($subject) < 3 || mb_strlen($subject) > 200) {
            throw ValidationException::withMessages([
                'subject' => [__('agricart.inbox.errors.subject')],
            ]);
        }

        if ($body === '' || mb_strlen($body) < 2 || mb_strlen($body) > 5000) {
            throw ValidationException::withMessages([
                'body' => [__('agricart.inbox.errors.body')],
            ]);
        }

        return DB::transaction(function () use ($customer, $admin, $subject, $body): AgricartConversation {
            $conversation = AgricartConversation::query()->create([
                'customer_id' => $customer->id,
                'subject' => $subject,
                'topic' => AgricartConversation::TOPIC_GENERAL,
                'status' => AgricartConversation::STATUS_OPEN,
                'assigned_to_user_id' => $admin->id,
                'admin_last_read_at' => now(),
                'customer_last_read_at' => null,
            ]);

            $this->appendMessage(
                $conversation,
                AgricartMessage::SENDER_ADMIN,
                $body,
                user: $admin,
            );

            $conversation->update(['admin_last_read_at' => $this->readAt($conversation)]);
            $this->markStaffRead($conversation, $admin);

            return $conversation->fresh(['messages']) ?? $conversation;
        });
    }

    public function replyAsCustomer(AgricartConversation $conversation, Customer $customer, string $body): AgricartMessage
    {
        if ((int) $conversation->customer_id !== (int) $customer->id) {
            abort(403);
        }

        if ($conversation->status === AgricartConversation::STATUS_CLOSED) {
            $conversation->update(['status' => AgricartConversation::STATUS_OPEN]);
        }

        $message = $this->appendMessage(
            $conversation,
            AgricartMessage::SENDER_CUSTOMER,
            $body,
            customer: $customer,
        );

        $conversation->update(['customer_last_read_at' => $this->readAt($conversation)]);
        $this->customerSupportBridge->markReadByCustomer($conversation, $customer);

        return $message;
    }

    public function replyAsAdmin(AgricartConversation $conversation, User $admin, string $body): AgricartMessage
    {
        if ($conversation->status === AgricartConversation::STATUS_CLOSED) {
            $conversation->status = AgricartConversation::STATUS_OPEN;
        }

        if ($conversation->assigned_to_user_id === null) {
            $conversation->assigned_to_user_id = $admin->id;
        }

        $message = $this->appendMessage(
            $conversation,
            AgricartMessage::SENDER_ADMIN,
            $body,
            user: $admin,
        );

        $conversation->forceFill([
            'assigned_to_user_id' => $conversation->assigned_to_user_id ?: $admin->id,
            'admin_last_read_at' => $this->readAt($conversation),
        ])->save();
        $this->markStaffRead($conversation, $admin);
        $this->customerSupportBridge->markReadByAdmin($conversation, $admin);

        return $message;
    }

    public function markReadByCustomer(AgricartConversation $conversation, Customer $customer): void
    {
        if ((int) $conversation->customer_id !== (int) $customer->id) {
            abort(403);
        }

        $conversation->update(['customer_last_read_at' => $this->readAt($conversation)]);
        $this->customerSupportBridge->markReadByCustomer($conversation, $customer);
    }

    public function markReadByAdmin(AgricartConversation $conversation, ?User $admin = null): void
    {
        $conversation->update(['admin_last_read_at' => $this->readAt($conversation)]);

        if ($admin instanceof User) {
            $this->markStaffRead($conversation, $admin);
        }

        $this->customerSupportBridge->markReadByAdmin($conversation, $admin);
    }

    private function markStaffRead(AgricartConversation $conversation, User $admin): void
    {
        if (! Schema::hasTable('agricart_conversation_reads')) {
            return;
        }

        AgricartConversationRead::query()->updateOrCreate(
            [
                'agricart_conversation_id' => $conversation->id,
                'user_id' => $admin->id,
            ],
            ['last_read_at' => $this->readAt($conversation)],
        );
    }

    private function readAt(AgricartConversation $conversation): \Illuminate\Support\Carbon
    {
        $readAt = now();
        $lastAt = $conversation->last_message_at;

        if ($lastAt !== null && $readAt->lt($lastAt)) {
            return $lastAt->copy();
        }

        return $readAt;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\AgricartConversation>  $query
     */
    private function constrainWaitingOnCustomer($query): void
    {
        $query->whereExists(function ($inner): void {
            $inner->selectRaw('1')
                ->from('agricart_messages as latest')
                ->whereColumn('latest.agricart_conversation_id', 'agricart_conversations.id')
                ->where('latest.sender_type', AgricartMessage::SENDER_CUSTOMER)
                ->whereRaw('latest.id = (
                    select max(m.id) from agricart_messages as m
                    where m.agricart_conversation_id = agricart_conversations.id
                )');
        });
    }

    public function close(AgricartConversation $conversation): void
    {
        $conversation->update(['status' => AgricartConversation::STATUS_CLOSED]);
        $this->customerSupportBridge->syncStatus($conversation);
    }

    public function reopen(AgricartConversation $conversation): void
    {
        $conversation->update(['status' => AgricartConversation::STATUS_OPEN]);
        $this->customerSupportBridge->syncStatus($conversation);
    }

    public function notifyReviewStatus(ProductReview $review, string $status): void
    {
        if ($review->customer_id === null) {
            return;
        }

        $eventKey = match ($status) {
            ProductReview::STATUS_APPROVED => 'review.approved',
            ProductReview::STATUS_REJECTED => 'review.rejected',
            ProductReview::STATUS_HIDDEN => 'review.hidden',
            ProductReview::STATUS_PENDING => 'review.pending',
            default => null,
        };

        if ($eventKey === null) {
            return;
        }

        $productName = $review->product?->name_en ?: __('agricart.inbox.product_fallback');

        $body = match ($eventKey) {
            'review.approved' => __('agricart.inbox.system.review_approved', ['product' => $productName]),
            'review.rejected' => __('agricart.inbox.system.review_rejected', ['product' => $productName]),
            'review.hidden' => __('agricart.inbox.system.review_hidden', ['product' => $productName]),
            'review.pending' => __('agricart.inbox.system.review_pending', ['product' => $productName]),
            default => null,
        };

        if ($body === null) {
            return;
        }

        $conversation = $this->conversationForReview($review);
        $this->appendSystemMessage($conversation, $body, $eventKey, [
            'product_review_id' => $review->id,
            'status' => $status,
        ]);
    }

    public function notifyOrderPlaced(OnlineSaleOrder $order): void
    {
        if ($order->customer_id === null) {
            return;
        }

        $conversation = $this->conversationForOrder($order);
        $this->appendSystemMessage(
            $conversation,
            __('agricart.inbox.system.order_placed', ['order' => $order->order_number]),
            'order.placed',
            ['online_sale_order_id' => $order->id],
        );
    }

    public function notifyOrderShipped(OnlineSaleOrder $order): void
    {
        if ($order->customer_id === null) {
            return;
        }

        $conversation = $this->conversationForOrder($order);
        $this->appendSystemMessage(
            $conversation,
            __('agricart.inbox.system.order_shipped', ['order' => $order->order_number]),
            'order.shipped',
            ['online_sale_order_id' => $order->id],
        );
    }

    public function notifyOrderDelivered(OnlineSaleOrder $order): void
    {
        if ($order->customer_id === null) {
            return;
        }

        $conversation = $this->conversationForOrder($order);
        $this->appendSystemMessage(
            $conversation,
            __('agricart.inbox.system.order_delivered', ['order' => $order->order_number]),
            'order.delivered',
            ['online_sale_order_id' => $order->id],
        );
    }

    public function notifyPaymentProofVerified(OnlineSaleOrder $order): void
    {
        if ($order->customer_id === null) {
            return;
        }

        $conversation = $this->conversationForOrder($order);
        $this->appendSystemMessage(
            $conversation,
            __('agricart.inbox.system.payment_verified', ['order' => $order->order_number]),
            'order.payment_verified',
            ['online_sale_order_id' => $order->id],
        );
    }

    public function notifyPaymentProofRejected(OnlineSaleOrder $order): void
    {
        if ($order->customer_id === null) {
            return;
        }

        $conversation = $this->conversationForOrder($order);
        $this->appendSystemMessage(
            $conversation,
            __('agricart.inbox.system.payment_rejected', ['order' => $order->order_number]),
            'order.payment_rejected',
            ['online_sale_order_id' => $order->id],
        );
    }

    public function conversationForOrder(OnlineSaleOrder $order): AgricartConversation
    {
        $existing = AgricartConversation::query()
            ->where('online_sale_order_id', $order->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return AgricartConversation::query()->create([
            'customer_id' => $order->customer_id,
            'subject' => __('agricart.inbox.subjects.order', ['order' => $order->order_number]),
            'topic' => AgricartConversation::TOPIC_ORDER,
            'status' => AgricartConversation::STATUS_OPEN,
            'online_sale_order_id' => $order->id,
        ]);
    }

    public function conversationForReview(ProductReview $review): AgricartConversation
    {
        $existing = AgricartConversation::query()
            ->where('product_review_id', $review->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $productName = $review->product?->name_en ?: __('agricart.inbox.product_fallback');

        return AgricartConversation::query()->create([
            'customer_id' => $review->customer_id,
            'subject' => __('agricart.inbox.subjects.review', ['product' => $productName]),
            'topic' => AgricartConversation::TOPIC_REVIEW,
            'status' => AgricartConversation::STATUS_OPEN,
            'product_review_id' => $review->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function appendSystemMessage(
        AgricartConversation $conversation,
        string $body,
        ?string $eventKey = null,
        array $meta = [],
    ): AgricartMessage {
        return $this->appendMessage(
            $conversation,
            AgricartMessage::SENDER_SYSTEM,
            $body,
            isSystem: true,
            eventKey: $eventKey,
            meta: $meta,
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function appendMessage(
        AgricartConversation $conversation,
        string $senderType,
        string $body,
        ?Customer $customer = null,
        ?User $user = null,
        bool $isSystem = false,
        ?string $eventKey = null,
        array $meta = [],
    ): AgricartMessage {
        $body = trim($body);

        if ($body === '' || mb_strlen($body) > 5000) {
            throw ValidationException::withMessages([
                'body' => [__('agricart.inbox.errors.body')],
            ]);
        }

        if (! in_array($senderType, AgricartMessage::SENDER_TYPES, true)) {
            throw ValidationException::withMessages([
                'body' => [__('agricart.inbox.errors.body')],
            ]);
        }

        $message = AgricartMessage::query()->create([
            'agricart_conversation_id' => $conversation->id,
            'sender_type' => $senderType,
            'customer_id' => $customer?->id,
            'user_id' => $user?->id,
            'body' => $body,
            'is_system' => $isSystem || $senderType === AgricartMessage::SENDER_SYSTEM,
            'event_key' => $eventKey,
            'meta' => $meta !== [] ? $meta : null,
        ]);

        $lastAt = $message->created_at ?? now();

        if ($senderType === AgricartMessage::SENDER_CUSTOMER
            && $conversation->admin_last_read_at !== null
            && $lastAt->lte($conversation->admin_last_read_at)) {
            $lastAt = $conversation->admin_last_read_at->copy()->addSecond();
        }

        if ($senderType !== AgricartMessage::SENDER_CUSTOMER
            && $conversation->customer_last_read_at !== null
            && $lastAt->lte($conversation->customer_last_read_at)) {
            $lastAt = $conversation->customer_last_read_at->copy()->addSecond();
        }

        if ($message->created_at === null || $lastAt->ne($message->created_at)) {
            $message->forceFill(['created_at' => $lastAt])->save();
        }

        $conversation->forceFill([
            'last_message_at' => $lastAt,
            'status' => AgricartConversation::STATUS_OPEN,
        ])->save();

        $this->customerSupportBridge->mirrorAgricartMessage($conversation->fresh() ?? $conversation, $message);

        return $message;
    }
}
