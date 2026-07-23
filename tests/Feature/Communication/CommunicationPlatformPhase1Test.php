<?php

namespace Tests\Feature\Communication;

use App\Contracts\Communication\AiAssistPort;
use App\Contracts\Communication\CallSessionPort;
use App\Contracts\Communication\NotifierPort;
use App\Contracts\Communication\PresencePort;
use App\Models\Category;
use App\Models\Communication\Channel;
use App\Models\Communication\Conversation;
use App\Models\Communication\Message;
use App\Models\Communication\Participant;
use App\Models\Communication\TaskLink;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Communication\AttachmentService;
use App\Services\Communication\ChannelRegistry;
use App\Services\Communication\ConversationService;
use App\Services\Communication\LinkService;
use App\Services\Communication\MessageService;
use App\Services\Communication\ParticipantService;
use App\Services\Communication\ReadStateService;
use App\Services\Communication\Support\ActorRef;
use App\Services\Communication\TaskBridgeService;
use App\Services\Staff\StaffMessagingService;
use App\Services\Store\AgricartInboxService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CommunicationPlatformPhase1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(ChannelRegistry::class)->syncDefinitions(
            config('communication.default_channels', []),
        );
    }

    public function test_channel_extensibility_adds_vendor_without_core_code_change(): void
    {
        $registry = app(ChannelRegistry::class);
        $registry->syncDefinitions([
            'vendor' => [
                'name' => 'Vendor Channel',
                'allowed_actor_types' => ['user', 'system'],
                'allows_groups' => true,
                'allows_attachments' => true,
                'allowed_mime_groups' => ['image', 'document'],
                'allows_tasks' => false,
                'visibility' => 'participants',
                'settings' => ['permission' => 'comms.access'],
                'sort_order' => 99,
            ],
        ]);

        $this->assertDatabaseHas('comm_channels', ['key' => 'vendor']);

        $admin = $this->superAdmin();
        $peer = User::factory()->create(['status' => User::STATUS_ACTIVE, 'role_id' => $admin->role_id]);

        $conversation = app(ConversationService::class)->create(
            'vendor',
            ActorRef::user($admin->id),
            [ActorRef::user($peer->id)],
            ['subject' => 'Vendor intro', 'type' => Conversation::TYPE_GROUP],
        );

        $this->assertSame('vendor', $conversation->channel->key);
        $this->assertCount(2, $conversation->participants);
    }

    public function test_polymorphic_participants_and_multi_record_links(): void
    {
        $admin = $this->superAdmin();
        $customer = $this->customer('comms-link@test.test');
        $product = $this->product('comms-product');
        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-COMMS-1',
            'order_date' => today(),
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
            'customer_id' => $customer->id,
        ]);

        $conversation = app(ConversationService::class)->create(
            'customer_support',
            ActorRef::user($admin->id),
            [ActorRef::customer($customer->id)],
            ['subject' => 'Order help'],
        );

        $links = app(LinkService::class);
        $links->attach($conversation, ActorRef::user($admin->id), 'online_sale_order', $order->id);
        $links->attach($conversation, ActorRef::user($admin->id), 'product', $product->id);

        $forOrder = $links->conversationsFor('online_sale_order', $order->id);
        $this->assertTrue($forOrder->contains('id', $conversation->id));
        $this->assertCount(2, $conversation->fresh()->links);
    }

    public function test_messages_support_reply_edit_soft_delete_and_system_idempotency(): void
    {
        $admin = $this->superAdmin();
        $peer = User::factory()->create(['status' => User::STATUS_ACTIVE, 'role_id' => $admin->role_id]);
        $messages = app(MessageService::class);

        $conversation = app(ConversationService::class)->create(
            'staff_ops',
            ActorRef::user($admin->id),
            [ActorRef::user($peer->id)],
        );

        $first = $messages->postText($conversation, ActorRef::user($admin->id), 'Hello team');
        $reply = $messages->postText($conversation, ActorRef::user($peer->id), 'Reply here', $first->id);
        $this->assertSame($first->id, $reply->parent_id);

        $edited = $messages->edit($reply, ActorRef::user($peer->id), 'Reply edited');
        $this->assertSame('Reply edited', $edited->body);
        $this->assertNotNull($edited->edited_at);

        $deleted = $messages->softDelete($reply, ActorRef::user($peer->id));
        $this->assertNotNull($deleted->deleted_at);

        $systemA = $messages->postSystem($conversation, 'Order forwarded', 'order.forwarded.1');
        $systemB = $messages->postSystem($conversation, 'Order forwarded again', 'order.forwarded.1');
        $this->assertSame($systemA->id, $systemB->id);
    }

    public function test_unread_counts_use_last_read_message_id(): void
    {
        $admin = $this->superAdmin();
        $peer = User::factory()->create(['status' => User::STATUS_ACTIVE, 'role_id' => $admin->role_id]);
        $messages = app(MessageService::class);
        $reads = app(ReadStateService::class);

        $conversation = app(ConversationService::class)->create(
            'staff_ops',
            ActorRef::user($admin->id),
            [ActorRef::user($peer->id)],
        );

        $messages->postText($conversation, ActorRef::user($admin->id), 'Ping');
        $this->assertSame(1, $reads->unreadConversationCount(ActorRef::user($peer->id)));
        $this->assertTrue($reads->isUnreadFor($conversation->fresh(), ActorRef::user($peer->id)));

        $reads->markRead($conversation->fresh(), ActorRef::user($peer->id));
        $this->assertSame(0, $reads->unreadConversationCount(ActorRef::user($peer->id)));
    }

    public function test_attachments_respect_channel_mime_policy(): void
    {
        Storage::fake('local');

        $admin = $this->superAdmin();
        $peer = User::factory()->create(['status' => User::STATUS_ACTIVE, 'role_id' => $admin->role_id]);
        $attachments = app(AttachmentService::class);

        $conversation = app(ConversationService::class)->create(
            'staff_ops',
            ActorRef::user($admin->id),
            [ActorRef::user($peer->id)],
        );

        $created = $attachments->postWithFiles(
            $conversation,
            ActorRef::user($admin->id),
            [UploadedFile::fake()->create('quote.pdf', 120, 'application/pdf')],
            'See PDF',
        );

        $this->assertCount(1, $created);
        $this->assertSame('document', $created[0]->kind);

        Channel::query()->where('key', 'staff_ops')->update([
            'allowed_mime_groups' => ['image'],
        ]);

        $this->expectException(ValidationException::class);
        $attachments->postWithFiles(
            $conversation->fresh(['channel']),
            ActorRef::user($admin->id),
            [UploadedFile::fake()->create('notes.pdf', 80, 'application/pdf')],
        );
    }

    public function test_authorization_blocks_non_participant_and_observer_post_and_record_link(): void
    {
        $admin = $this->superAdmin();
        $member = User::factory()->create(['status' => User::STATUS_ACTIVE, 'role_id' => $admin->role_id]);
        $outsider = User::factory()->create(['status' => User::STATUS_ACTIVE, 'role_id' => $admin->role_id]);
        $limited = $this->userWithPermissions(['comms.access', 'comms.channel.staff_ops.access']);

        $conversation = app(ConversationService::class)->create(
            'staff_ops',
            ActorRef::user($admin->id),
            [ActorRef::user($member->id)],
        );

        app(ParticipantService::class)->add($conversation, ActorRef::user($limited->id), Participant::ROLE_OBSERVER);

        $messages = app(MessageService::class);

        try {
            $messages->postText($conversation, ActorRef::user($outsider->id), 'Should fail');
            $this->fail('Outsider should not post.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        try {
            $messages->postText($conversation, ActorRef::user($limited->id), 'Observer cannot post');
            $this->fail('Observer should not post.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        $linker = $this->userWithPermissions(['comms.access', 'comms.channel.staff_ops.access']);
        app(ParticipantService::class)->add($conversation, ActorRef::user($linker->id), Participant::ROLE_MEMBER);

        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-COMMS-DENY',
            'order_date' => today(),
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
        ]);

        $this->expectException(AuthorizationException::class);
        app(LinkService::class)->attach(
            $conversation,
            ActorRef::user($linker->id),
            'online_sale_order',
            $order->id,
        );
    }

    public function test_task_bridge_create_and_complete(): void
    {
        $admin = $this->superAdmin();
        $assignee = User::factory()->create(['status' => User::STATUS_ACTIVE, 'role_id' => $admin->role_id]);

        $conversation = app(ConversationService::class)->create(
            'task',
            ActorRef::user($admin->id),
            [ActorRef::user($assignee->id)],
            ['subject' => 'Packing follow-up'],
        );

        $task = app(TaskBridgeService::class)->createFromConversation(
            $conversation,
            ActorRef::user($admin->id),
            [
                'title' => 'Confirm packing list',
                'assignable_type' => 'user',
                'assignable_id' => $assignee->id,
                'priority' => TaskLink::PRIORITY_HIGH,
                'due_at' => now()->addDay()->toDateTimeString(),
            ],
        );

        $this->assertSame(TaskLink::STATUS_OPEN, $task->status);

        $done = app(TaskBridgeService::class)->complete($task, ActorRef::user($assignee->id));
        $this->assertSame(TaskLink::STATUS_DONE, $done->status);
        $this->assertSame('user', $done->completed_by_type);
        $this->assertSame($assignee->id, $done->completed_by_id);
        $this->assertNotNull($done->completed_at);
    }

    public function test_ports_resolve_to_null_adapters(): void
    {
        $this->assertInstanceOf(
            \App\Services\Communication\Ports\NullNotifier::class,
            app(NotifierPort::class),
        );
        $this->assertInstanceOf(
            \App\Services\Communication\Ports\NullPresence::class,
            app(PresencePort::class),
        );
        $this->assertInstanceOf(
            \App\Services\Communication\Ports\NullAiAssist::class,
            app(AiAssistPort::class),
        );
        $this->assertInstanceOf(
            \App\Services\Communication\Ports\NullCallSession::class,
            app(CallSessionPort::class),
        );

        $this->assertSame('unsupported', app(CallSessionPort::class)->startSession(1, 'audio')['status']);
    }

    public function test_legacy_staff_and_agricart_inbox_services_still_resolve(): void
    {
        $this->assertInstanceOf(StaffMessagingService::class, app(StaffMessagingService::class));
        $this->assertInstanceOf(AgricartInboxService::class, app(AgricartInboxService::class));
        $this->assertSame(0, app(AgricartInboxService::class)->unreadCountForAdmin());
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_system' => true],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param  list<string>  $keys
     */
    private function userWithPermissions(array $keys): User
    {
        $role = Role::query()->create([
            'name' => 'Comms Limited '.uniqid(),
            'slug' => 'comms-limited-'.uniqid(),
            'is_system' => false,
        ]);

        $ids = [];

        foreach ($keys as $index => $key) {
            $permission = Permission::query()->firstOrCreate(
                ['key' => $key],
                [
                    'group_key' => 'comms',
                    'label_en' => $key,
                    'label_ur' => $key,
                    'sort_order' => $index,
                ],
            );
            $ids[] = $permission->id;
        }

        $role->permissions()->sync($ids);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function customer(string $email): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'CUS-'.substr(md5($email), 0, 8),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Comms',
            'last_name' => 'Customer',
            'email' => $email,
            'phone' => '0300'.random_int(1000000, 9999999),
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);
    }

    private function product(string $slug): Product
    {
        $category = Category::query()->create([
            'slug' => 'cat-'.$slug,
            'code' => 'CAT-'.strtoupper(substr(md5($slug), 0, 6)),
            'status' => 'active',
            'name_en' => 'Comms Category',
            'name_ur' => 'مواصلات زمرہ',
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-'.strtoupper(substr(md5($slug), 0, 6)),
            'sku' => 'SKU-'.$slug,
            'name_en' => 'Comms Product',
            'name_ur' => 'مواصلات پروڈکٹ',
            'slug' => $slug,
        ]);
    }
}
