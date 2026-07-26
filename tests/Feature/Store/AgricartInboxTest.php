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
        $this->assertSame(0, $inbox->unreadCountForAdmin());
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
}
