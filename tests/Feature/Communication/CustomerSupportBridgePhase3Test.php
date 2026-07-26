<?php

namespace Tests\Feature\Communication;

use App\Livewire\Store\ListAgricartInbox;
use App\Models\AgricartConversation;
use App\Models\AgricartMessage;
use App\Models\Category;
use App\Models\Communication\ConversationLink;
use App\Models\Communication\Message;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Role;
use App\Models\User;
use App\Services\Communication\ChannelRegistry;
use App\Services\Communication\CustomerSupportBridge;
use App\Services\Store\AgricartInboxService;
use App\Services\Store\OnlineStoreOrderNotifier;
use App\Services\Store\ProductReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerSupportBridgePhase3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(ChannelRegistry::class)->syncDefinitions(
            config('communication.default_channels', []),
        );
    }

    public function test_customer_contact_dual_writes_to_customer_support_channel(): void
    {
        $customer = $this->customer('phase3-contact@test.test');

        $this->actingAs($customer, 'customer')->post(route('store.contact-message'), [
            'name' => 'Ali Khan',
            'phone' => '03001234567',
            'email' => 'ali@example.com',
            'message' => 'Please help with seed availability.',
            'source_url' => route('store.home'),
            'company_website' => '',
        ])->assertRedirect();

        $legacy = AgricartConversation::query()->sole();
        $legacyMessage = AgricartMessage::query()->where('agricart_conversation_id', $legacy->id)->sole();

        $conversation = app(CustomerSupportBridge::class)->findConversation($legacy);
        $this->assertNotNull($conversation);
        $this->assertSame('customer_support', $conversation->channel->key);
        $this->assertSame($legacy->id, $conversation->meta['legacy_agricart_conversation_id'] ?? null);
        $this->assertSame($customer->id, $legacy->customer_id);

        $this->assertTrue(
            Message::query()
                ->where('conversation_id', $conversation->id)
                ->where('event_key', 'legacy.agricart_message.'.$legacyMessage->id)
                ->exists()
        );
    }

    public function test_customer_thread_and_admin_reply_are_mirrored_with_links(): void
    {
        $customer = $this->customer('phase3-customer@test.test');
        $admin = $this->superAdmin();
        $inbox = app(AgricartInboxService::class);

        $legacy = $inbox->startGeneralConversation(
            $customer,
            'Delivery question',
            'When will my parcel leave Lahore?',
        );

        $inbox->replyAsAdmin($legacy, $admin, 'It leaves tomorrow morning.');

        $conversation = app(CustomerSupportBridge::class)->findConversation($legacy->fresh());
        $this->assertNotNull($conversation);
        $this->assertTrue(
            Message::query()
                ->where('conversation_id', $conversation->id)
                ->where('body', 'It leaves tomorrow morning.')
                ->exists()
        );
        $this->assertTrue(
            ConversationLink::query()
                ->where('conversation_id', $conversation->id)
                ->where('linkable_type', 'customer')
                ->where('linkable_id', $customer->id)
                ->exists()
        );

        Livewire::actingAs($admin)
            ->test(ListAgricartInbox::class)
            ->call('openView', $legacy->id)
            ->assertSee(__('agricart.inbox.admin.bridged_to_platform', ['id' => $conversation->id]), false);
    }

    public function test_order_system_notice_mirrors_and_links_order(): void
    {
        Notification::fake();

        $customer = $this->customer('phase3-order@test.test');
        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-P3-1',
            'order_date' => today(),
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
            'customer_id' => $customer->id,
        ]);

        app(OnlineStoreOrderNotifier::class)->orderPlaced($order);

        $legacy = AgricartConversation::query()
            ->where('online_sale_order_id', $order->id)
            ->sole();

        $conversation = app(CustomerSupportBridge::class)->findConversation($legacy);
        $this->assertNotNull($conversation);
        $this->assertTrue(
            ConversationLink::query()
                ->where('conversation_id', $conversation->id)
                ->where('linkable_type', 'online_sale_order')
                ->where('linkable_id', $order->id)
                ->exists()
        );
        $this->assertTrue(
            Message::query()
                ->where('conversation_id', $conversation->id)
                ->where('message_type', Message::TYPE_SYSTEM)
                ->exists()
        );
    }

    public function test_review_approval_mirrors_with_product_links(): void
    {
        $customer = $this->customer('phase3-review@test.test');
        $product = $this->product('phase3-review-product');
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

        $legacy = AgricartConversation::query()
            ->where('product_review_id', $review->id)
            ->sole();

        $conversation = app(CustomerSupportBridge::class)->findConversation($legacy);
        $this->assertNotNull($conversation);
        $this->assertTrue(
            ConversationLink::query()
                ->where('conversation_id', $conversation->id)
                ->where('linkable_type', 'product_review')
                ->where('linkable_id', $review->id)
                ->exists()
        );
        $this->assertTrue(
            ConversationLink::query()
                ->where('conversation_id', $conversation->id)
                ->where('linkable_type', 'product')
                ->where('linkable_id', $product->id)
                ->exists()
        );
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

    private function customer(string $email): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'CUS-'.substr(md5($email), 0, 8),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Phase',
            'last_name' => 'Three',
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
            'name_en' => 'Phase3 Category',
            'name_ur' => 'مرحلہ زمرہ',
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-'.strtoupper(substr(md5($slug), 0, 6)),
            'sku' => 'SKU-'.$slug,
            'name_en' => 'Phase3 Product',
            'name_ur' => 'مرحلہ پروڈکٹ',
            'slug' => $slug,
        ]);
    }
}
