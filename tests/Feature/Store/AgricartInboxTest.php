<?php

namespace Tests\Feature\Store;

use App\Livewire\Store\ListAgricartInbox;
use App\Models\AgricartConversation;
use App\Models\AgricartMessage;
use App\Models\Category;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Role;
use App\Models\User;
use App\Services\Store\AgricartInboxService;
use App\Services\Store\OnlineStoreOrderNotifier;
use App\Services\Store\ProductReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AgricartInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_contact_is_blocked_and_redirected_to_login(): void
    {
        $this->post(route('store.contact-message'), [
            'name' => 'Ali Khan',
            'phone' => '03001234567',
            'email' => 'ali@example.com',
            'message' => 'Please help with seed availability.',
            'source_url' => route('store.home'),
            'company_website' => '',
        ])
            ->assertRedirect(route('store.customer.login', ['redirect' => '/']))
            ->assertSessionHas('customer_auth_status');

        $this->assertDatabaseCount('agricart_conversations', 0);
        $this->assertDatabaseCount('agricart_messages', 0);
    }

    public function test_logged_in_customer_contact_redirects_to_inbox_thread(): void
    {
        $customer = $this->createCustomer('inbox-contact@test.test');

        $response = $this->actingAs($customer, 'customer')->post(route('store.contact-message'), [
            'name' => $customer->displayName(),
            'phone' => '03001112233',
            'message' => 'I need help with my recent order.',
            'company_website' => '',
        ]);

        $conversation = AgricartConversation::query()->sole();
        $response->assertRedirect(route('store.customer.inbox.show', $conversation->id));
        $this->assertSame($customer->id, $conversation->customer_id);
    }

    public function test_customer_can_start_and_reply_in_general_thread(): void
    {
        $customer = $this->createCustomer('inbox-reply@test.test');

        $this->actingAs($customer, 'customer')
            ->post(route('store.customer.inbox.store'), [
                'subject' => 'Delivery question',
                'body' => 'When will my parcel leave Lahore?',
            ])
            ->assertRedirect();

        $conversation = AgricartConversation::query()->sole();
        $this->assertSame(AgricartConversation::TOPIC_GENERAL, $conversation->topic);

        $this->actingAs($customer, 'customer')
            ->post(route('store.customer.inbox.reply', $conversation->id), [
                'body' => 'Also please confirm the carrier.',
            ])
            ->assertRedirect(route('store.customer.inbox.show', $conversation->id));

        $this->assertDatabaseHas('agricart_messages', [
            'agricart_conversation_id' => $conversation->id,
            'body' => 'Also please confirm the carrier.',
            'sender_type' => AgricartMessage::SENDER_CUSTOMER,
        ]);
    }

    public function test_admin_reply_marks_customer_thread_unread(): void
    {
        $customer = $this->createCustomer('inbox-admin@test.test');
        $admin = $this->superAdmin();
        $inbox = app(AgricartInboxService::class);

        $conversation = $inbox->startGeneralConversation(
            $customer,
            'Need help',
            'Can you call me about fertilizer?',
        );

        Livewire::actingAs($admin)
            ->test(ListAgricartInbox::class)
            ->call('openView', $conversation->id)
            ->set('replyText', 'Yes — we will call you today.')
            ->call('sendReply')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('agricart_messages', [
            'agricart_conversation_id' => $conversation->id,
            'sender_type' => AgricartMessage::SENDER_ADMIN,
            'body' => 'Yes — we will call you today.',
        ]);

        $this->assertSame(1, $inbox->unreadCountForCustomer($customer->fresh()));
        $this->assertSame($admin->id, $conversation->fresh()->assigned_to_user_id);
        $this->assertSame(0, $inbox->unreadCountForAdmin($admin));
        $this->assertSame(0, $inbox->unreadCountForAdmin());
    }

    public function test_legacy_team_read_stays_read_until_customer_writes_again(): void
    {
        $customer = $this->createCustomer('inbox-legacy-read@test.test');
        $admin = $this->superAdmin();
        $inbox = app(AgricartInboxService::class);

        $conversation = $inbox->startGeneralConversation(
            $customer,
            'Legacy read',
            'Please check stock.',
        );

        $conversation->update(['admin_last_read_at' => now()->addSecond()]);
        \App\Models\AgricartConversationRead::query()->delete();

        $this->assertSame(0, $inbox->unreadCountForAdmin($admin));
        $this->assertFalse($conversation->fresh()->unreadForStaff($admin));

        $inbox->replyAsCustomer($conversation->fresh(), $customer, 'Any update?');

        $this->assertSame(1, $inbox->unreadCountForAdmin($admin));
        $this->assertTrue($conversation->fresh()->unreadForStaff($admin));
    }

    public function test_admin_reply_assigns_thread_and_hides_unread_from_other_staff(): void
    {
        $customer = $this->createCustomer('inbox-assign@test.test');
        $agentA = $this->superAdmin(['name' => 'Ayesha Agent']);
        $agentB = $this->superAdmin(['name' => 'Bilal Agent']);
        $inbox = app(AgricartInboxService::class);

        $conversation = $inbox->startGeneralConversation(
            $customer,
            'Seed stock',
            'Do you have wheat seed in Lahore?',
        );

        $this->assertNull($conversation->assigned_to_user_id);
        $this->assertSame(1, $inbox->unreadCountForAdmin($agentA));
        $this->assertSame(1, $inbox->unreadCountForAdmin($agentB));

        $inbox->replyAsAdmin($conversation->fresh(), $agentA, 'Yes, we have stock this week.');

        $conversation->refresh();
        $this->assertSame($agentA->id, $conversation->assigned_to_user_id);
        $this->assertSame(0, $inbox->unreadCountForAdmin($agentA));
        $this->assertSame(0, $inbox->unreadCountForAdmin($agentB));

        $inbox->replyAsCustomer($conversation->fresh(), $customer, 'Please reserve 10 bags.');

        $this->assertSame(1, $inbox->unreadCountForAdmin($agentA));
        $this->assertSame(0, $inbox->unreadCountForAdmin($agentB));

        Livewire::actingAs($agentA)
            ->test(ListAgricartInbox::class)
            ->call('applyFilter', 'queue:mine')
            ->assertSee('Seed stock');

        Livewire::actingAs($agentB)
            ->test(ListAgricartInbox::class)
            ->call('applyFilter', 'queue:mine')
            ->assertDontSee('Seed stock');
    }

    public function test_staff_can_take_unassigned_conversation(): void
    {
        $customer = $this->createCustomer('inbox-take@test.test');
        $agent = $this->superAdmin(['name' => 'Take Agent']);
        $inbox = app(AgricartInboxService::class);

        $conversation = $inbox->startGeneralConversation(
            $customer,
            'Take chat',
            'Please help with delivery.',
        );

        Livewire::actingAs($agent)
            ->test(ListAgricartInbox::class)
            ->call('openView', $conversation->id)
            ->call('takeConversation', $conversation->id)
            ->assertHasNoErrors()
            ->assertSee('Take Agent')
            ->assertSee(__('agricart.inbox.admin.meta_handler'));

        $this->assertSame($agent->id, $conversation->fresh()->assigned_to_user_id);
        $this->assertSame(0, $inbox->unreadCountForAdmin($agent));
    }

    public function test_customer_sees_agricart_not_staff_name_and_read_receipts_work_both_ways(): void
    {
        $customer = $this->createCustomer('inbox-receipts@test.test');
        $agent = $this->superAdmin(['name' => 'Zainab Representative']);
        $inbox = app(AgricartInboxService::class);

        $conversation = $inbox->startGeneralConversation(
            $customer,
            'Receipts',
            'Can you confirm my order?',
        );

        $customerMessage = $conversation->messages()->where('sender_type', AgricartMessage::SENDER_CUSTOMER)->sole();
        $this->assertFalse($conversation->teamHasReadMessage($customerMessage));

        $adminMessage = $inbox->replyAsAdmin($conversation->fresh(), $agent, 'Yes, it is confirmed.');
        $conversation->refresh();
        $this->assertFalse($conversation->customerHasReadMessage($adminMessage));
        $this->assertTrue($conversation->teamHasReadMessage($customerMessage->fresh()));

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.inbox.show', $conversation->id))
            ->assertOk()
            ->assertSee(__('agricart.inbox.sender.agricart'))
            ->assertDontSee('Zainab Representative')
            ->assertSee(__('agricart.inbox.receipt.read_by_agricart'))
            ->assertSee(__('agricart.inbox.receipt.banner_agricart_replied'));

        $conversation->refresh();
        $this->assertTrue($conversation->customerHasReadMessage($adminMessage->fresh()));

        Livewire::actingAs($agent)
            ->test(ListAgricartInbox::class)
            ->call('openView', $conversation->id)
            ->assertSee('Zainab Representative')
            ->assertSee(__('agricart.inbox.receipt.banner_read'))
            ->assertSee(__('agricart.inbox.receipt.read_by_customer'))
            ->assertSee(__('agricart.inbox.queue.read_waiting'));
    }

    public function test_admin_list_shows_customer_read_progress_not_generic_open(): void
    {
        $customer = $this->createCustomer('inbox-queue@test.test');
        $admin = $this->superAdmin(['name' => 'Queue Agent']);
        $inbox = app(AgricartInboxService::class);

        $inbox->startAdminMessage(
            $customer,
            $admin,
            'Welcome to Agricart.pk',
            'Assalamu alaikum — welcome from Agricart.',
        );

        Livewire::actingAs($admin)
            ->test(ListAgricartInbox::class)
            ->assertSee(__('agricart.inbox.queue.sent_unread'))
            ->assertDontSee('1 message|1 messages')
            ->assertSee(trans_choice('agricart.inbox.admin.messages_count', 1))
            ->assertSee(__('agricart.inbox.admin.view'))
            ->assertSee('PKT');

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.inbox'))
            ->assertOk()
            ->assertSee(__('agricart.inbox.customer_queue.new_from_agricart'));

        $conversation = AgricartConversation::query()->where('customer_id', $customer->id)->sole();

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.inbox.show', $conversation->id))
            ->assertOk();

        Livewire::actingAs($admin)
            ->test(ListAgricartInbox::class)
            ->assertSee(__('agricart.inbox.queue.read_waiting'));
    }

    public function test_order_placed_creates_system_message_in_order_thread(): void
    {
        Notification::fake();

        $customer = $this->createCustomer('inbox-order@test.test');
        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-INBOX-1',
            'order_date' => today(),
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
            'customer_id' => $customer->id,
        ]);

        app(OnlineStoreOrderNotifier::class)->orderPlaced($order);

        $conversation = AgricartConversation::query()
            ->where('online_sale_order_id', $order->id)
            ->sole();

        $this->assertSame(AgricartConversation::TOPIC_ORDER, $conversation->topic);
        $this->assertDatabaseHas('agricart_messages', [
            'agricart_conversation_id' => $conversation->id,
            'sender_type' => AgricartMessage::SENDER_SYSTEM,
            'event_key' => 'order.placed',
            'is_system' => true,
        ]);
        $this->assertSame(0, app(AgricartInboxService::class)->unreadCountForAdmin());
        $this->assertSame(1, app(AgricartInboxService::class)->unreadCountForCustomer($customer));
    }

    public function test_review_approval_posts_system_notice(): void
    {
        $customer = $this->createCustomer('inbox-review@test.test');
        $product = $this->createProduct('inbox-review-product');
        $review = ProductReview::query()->create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'submission_path' => ProductReview::PATH_PROOF,
            'rating' => 5,
            'body' => 'Great product for wheat.',
            'status' => ProductReview::STATUS_PENDING,
        ]);

        app(ProductReviewService::class)->setStatus(
            $review,
            ProductReview::STATUS_APPROVED,
            $this->superAdmin(),
        );

        $conversation = AgricartConversation::query()
            ->where('product_review_id', $review->id)
            ->sole();

        $this->assertSame(AgricartConversation::TOPIC_REVIEW, $conversation->topic);
        $this->assertDatabaseHas('agricart_messages', [
            'agricart_conversation_id' => $conversation->id,
            'sender_type' => AgricartMessage::SENDER_SYSTEM,
            'event_key' => 'review.approved',
        ]);
    }

    public function test_admin_can_send_inbox_message_from_customers_list(): void
    {
        $admin = $this->superAdmin();
        $customer = $this->createCustomer('welcome-inbox@test.test');

        $this->actingAs($admin);

        Livewire::test(\App\Livewire\Customers\ListCustomers::class)
            ->call('openSendMessage', $customer->id)
            ->assertSet('showMessagePanel', true)
            ->set('messageSubject', 'Welcome to Agricart')
            ->set('messageBody', 'Assalamu alaikum — welcome and good wishes from Agricart.')
            ->call('sendMessageToCustomer')
            ->assertSet('showMessagePanel', false)
            ->assertSet('portableStatusTone', 'success')
            ->assertSet('messageSendReport.customer', $customer->displayName())
            ->assertSet('messageSendReport.subject', 'Welcome to Agricart');

        $conversation = AgricartConversation::query()->where('customer_id', $customer->id)->sole();
        $this->assertSame('Welcome to Agricart', $conversation->subject);
        $this->assertNull($conversation->customer_last_read_at);
        $this->assertSame(1, app(AgricartInboxService::class)->unreadCountForCustomer($customer));

        $this->assertDatabaseHas('agricart_messages', [
            'agricart_conversation_id' => $conversation->id,
            'sender_type' => AgricartMessage::SENDER_ADMIN,
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_can_save_and_reuse_customer_inbox_message_template(): void
    {
        $admin = $this->superAdmin();
        $first = $this->createCustomer('template-first@test.test');
        $second = $this->createCustomer('template-second@test.test');

        $this->actingAs($admin);

        Livewire::test(\App\Livewire\Customers\ListCustomers::class)
            ->call('openSendMessage', $first->id)
            ->set('messageTemplateId', null)
            ->set('messageTemplateName', 'Launch update')
            ->set('messageSubject', 'Launch update for '.$first->displayName())
            ->set('messageBody', "Hello {$first->displayName()},\nSee you on 14 August.")
            ->call('saveMessageTemplate');

        $template = \App\Models\ContactMessageTemplate::query()->where('name_en', 'Launch update')->sole();
        $this->assertSame('Launch update for :name', $template->subject_en);
        $this->assertStringContainsString('Hello :name,', (string) $template->body_en);
        $this->assertStringNotContainsString($first->displayName(), (string) $template->body_en);

        Livewire::test(\App\Livewire\Customers\ListCustomers::class)
            ->call('openSendMessage', $second->id)
            ->set('messageTemplateId', $template->id)
            ->assertSet('messageSubject', 'Launch update for '.$second->displayName())
            ->assertSet('messageBody', "Hello {$second->displayName()},\nSee you on 14 August.");
    }

    private function createCustomer(string $email): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'CUS-'.substr(md5($email), 0, 8),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Inbox',
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

    private function createProduct(string $slug): Product
    {
        $category = Category::query()->create([
            'slug' => 'cat-'.$slug,
            'code' => 'CAT-'.strtoupper(substr(md5($slug), 0, 6)),
            'status' => 'active',
            'name_en' => 'Inbox Category',
            'name_ur' => 'ان باکس زمرہ',
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-'.strtoupper(substr(md5($slug), 0, 6)),
            'sku' => 'SKU-'.$slug,
            'name_en' => 'Inbox Product',
            'name_ur' => 'ان باکس پروڈکٹ',
            'slug' => $slug,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function superAdmin(array $overrides = []): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_system' => true],
        );

        return User::factory()->create(array_merge([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ], $overrides));
    }
}
