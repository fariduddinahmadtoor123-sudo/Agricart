<?php

namespace Tests\Feature\Communication;

use App\Livewire\Staff\ListInbox;
use App\Models\Category;
use App\Models\Communication\ConversationLink;
use App\Models\Communication\Message;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StaffMessage;
use App\Models\User;
use App\Services\Communication\ChannelRegistry;
use App\Services\Communication\StaffOpsBridge;
use App\Services\Staff\StaffMessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class StaffOpsBridgePhase2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(ChannelRegistry::class)->syncDefinitions(
            config('communication.default_channels', []),
        );
    }

    public function test_order_forward_dual_writes_to_staff_ops_channel(): void
    {
        [$admin, $order, $staff] = $this->seedScenario();

        $thread = app(StaffMessagingService::class)
            ->forwardOrderToStaff($order, $staff, $admin, 'invoice');

        $legacyMessage = StaffMessage::query()->where('thread_id', $thread->id)->latest('id')->first();
        $this->assertNotNull($legacyMessage);

        $conversation = app(StaffOpsBridge::class)->findConversation($thread);
        $this->assertNotNull($conversation);
        $this->assertSame('staff_ops', $conversation->channel->key);
        $this->assertSame($thread->id, $conversation->meta['legacy_staff_thread_id'] ?? null);

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
                ->where('event_key', 'legacy.staff_message.'.$legacyMessage->id)
                ->exists()
        );
    }

    public function test_staff_can_reply_in_inbox_and_mirror_updates(): void
    {
        [$admin, $order, $staff] = $this->seedScenario();

        $thread = app(StaffMessagingService::class)
            ->forwardOrderToStaff($order, $staff, $admin, 'invoice');

        Livewire::actingAs($staff)
            ->test(ListInbox::class)
            ->call('openThread', $thread->id)
            ->set('replyText', 'Packing started for this order.')
            ->call('sendReply')
            ->assertHasNoErrors()
            ->assertSee('Packing started for this order.', false);

        $conversation = app(StaffOpsBridge::class)->findConversation($thread->fresh());
        $this->assertNotNull($conversation);
        $this->assertTrue(
            Message::query()
                ->where('conversation_id', $conversation->id)
                ->where('body', 'Packing started for this order.')
                ->exists()
        );
    }

    public function test_legacy_inbox_list_still_works_after_bridge(): void
    {
        [$admin, $order, $staff] = $this->seedScenario();

        app(StaffMessagingService::class)->forwardOrderToStaff($order, $staff, $admin, 'invoice');

        Livewire::actingAs($staff)
            ->test(ListInbox::class)
            ->assertSee(__('staff_inbox.messages.order_forward_subject', ['order' => $order->order_number]), false);
    }

    /**
     * @return array{0: User, 1: OnlineSaleOrder, 2: User}
     */
    private function seedScenario(): array
    {
        $admin = $this->superAdmin();
        $staff = User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Bridge Staff',
            'email' => 'bridge-staff@test.test',
        ]);

        $category = Category::query()->create([
            'slug' => 'cat-bridge',
            'status' => 'active',
            'name_en' => 'Bridge Category',
            'name_ur' => 'برج زمرہ',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-BRG',
            'sku' => 'SKU-bridge',
            'name_en' => 'Bridge Product',
            'name_ur' => 'برج پروڈکٹ',
            'slug' => 'bridge-product',
        ]);

        $customer = Customer::query()->create([
            'customer_number' => 'CUS-BRIDGE1',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Bridge',
            'last_name' => 'Customer',
            'email' => 'bridge-customer@test.test',
            'phone' => '03001110001',
            'password' => Hash::make('password'),
            'address_line1' => '1 Bridge Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $sale = Sale::query()->create([
            'sale_number' => 'INV-BRG1',
            'customer_id' => $customer->id,
            'status' => Sale::STATUS_COMPLETED,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'subtotal' => 500,
            'total_amount' => 500,
            'amount_paid' => 500,
            'sale_date' => now()->toDateString(),
        ]);

        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-BRIDGE-1',
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'order_date' => now()->toDateString(),
            'locale' => 'en',
            'order_status' => OnlineSaleOrder::STATUS_CONVERTED,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            'payment_status' => 'paid',
            'subtotal' => 500,
            'total_amount' => 500,
            'amount_paid' => 500,
            'ship_contact_name' => 'Bridge Customer',
            'ship_phone' => '03001110001',
            'ship_address' => '1 Bridge Street',
            'ship_city' => 'Lahore',
            'converted_at' => now(),
            'queued_at' => now(),
            'queue_document' => OnlineSaleOrder::QUEUE_DOCUMENT_INVOICE,
        ]);

        OnlineSaleOrderItem::query()->create([
            'online_sale_order_id' => $order->id,
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => $product->name_en,
            'quantity' => 1,
            'unit_price' => 500,
            'line_total' => 500,
        ]);

        return [$admin, $order->fresh(), $staff];
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
