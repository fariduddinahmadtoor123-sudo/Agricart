<?php

namespace Tests\Feature\Sales;

use App\Livewire\OnlineSaleOrders\ListOrderDispatch;
use App\Livewire\Staff\ListInbox;
use App\Models\Category;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use App\Models\OnlineSaleOrderStaffAssignment;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StaffMessage;
use App\Models\StaffMessageThread;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Sales\OnlineOrderDispatchService;
use App\Services\Staff\StaffMessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class OnlineSaleOrderForwardTest extends TestCase
{
    use RefreshDatabase;

    public function test_forward_panel_lists_staff_only_for_inbox_send(): void
    {
        [$admin, $order] = $this->seedForwardScenario();
        $staff = $this->makeStaff($admin, 'Purchaser One', 'purchaser-one@test.test');

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->call('openForward', $order->id)
            ->assertSet('forwardOrderId', $order->id)
            ->assertSee($staff->name, false)
            ->assertSee(__('online_sale_orders.forward.send_inbox'), false)
            ->assertDontSee('Tor Hardware', false)
            ->assertDontSee(__('online_sale_orders.forward.matched_suppliers_heading'), false);
    }

    public function test_forward_to_inbox_creates_assignment_message_and_leaves_queue(): void
    {
        [$admin, $order] = $this->seedForwardScenario();
        $staff = $this->makeStaff($admin, 'Purchaser Two', 'purchaser-two@test.test');

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->call('openForward', $order->id)
            ->call('forwardToInbox', $staff->id)
            ->assertSet('forwardOrderId', null);

        $this->assertTrue(
            OnlineSaleOrderStaffAssignment::query()
                ->where('online_sale_order_id', $order->id)
                ->where('user_id', $staff->id)
                ->whereNotNull('sent_at')
                ->exists()
        );

        $this->assertTrue(
            StaffMessage::query()
                ->where('sender_id', $admin->id)
                ->whereHas('thread', function ($query) use ($order, $staff): void {
                    $query->where('context_type', StaffMessageThread::CONTEXT_ONLINE_SALE_ORDER)
                        ->where('context_id', $order->id)
                        ->whereHas('participants', fn ($p) => $p->where('user_id', $staff->id));
                })
                ->exists()
        );

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->assertDontSee($order->order_number, false);

        Livewire::actingAs($staff)
            ->test(ListInbox::class)
            ->assertSee(__('staff_inbox.messages.order_forward_subject', ['order' => $order->order_number]), false);
    }

    public function test_can_forward_order_to_self_and_shows_unread_badge(): void
    {
        [$admin, $order] = $this->seedForwardScenario();

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->call('openForward', $order->id)
            ->call('forwardToInbox', $admin->id)
            ->assertSet('forwardOrderId', null);

        $this->assertTrue(
            OnlineSaleOrderStaffAssignment::query()
                ->where('online_sale_order_id', $order->id)
                ->where('user_id', $admin->id)
                ->whereNotNull('sent_at')
                ->exists()
        );

        $this->actingAs($admin);
        $this->assertSame(1, app(StaffMessagingService::class)->unreadCountFor($admin));

        $summary = app(\App\Services\Notifications\TopbarNotificationService::class)->summary();
        $this->assertSame(1, $summary['inbox_unread']);
    }

    public function test_inbox_open_invoice_uses_sale_print_when_converted(): void
    {
        [$admin, $order] = $this->seedForwardScenario();
        $staff = $this->makeStaff($admin, 'Invoice Viewer', 'invoice-viewer@test.test');

        $url = app(OnlineOrderDispatchService::class)->documentUrl($order->fresh('sale'), 'invoice');

        $this->assertStringContainsString('/sales/', $url);
        $this->assertStringContainsString('/print', $url);

        $thread = app(StaffMessagingService::class)
            ->forwardOrderToStaff($order, $staff, $admin, 'invoice');

        $message = $thread->messages()->latest('id')->first();
        $this->assertNotNull($message);
        $this->assertSame('order_forward', $message->meta['type'] ?? null);

        Livewire::actingAs($staff)
            ->test(ListInbox::class)
            ->call('openThread', $thread->id)
            ->assertSee(__('pos.print.heading'), false)
            ->assertSee($order->order_number, false)
            ->assertSee('Forward Buyer', false)
            ->assertSee('Boom Sprayer', false)
            ->assertSee(__('staff_inbox.list.print_invoice'), false)
            ->assertSee(__('staff_inbox.list.print_packing'), false);
    }

    public function test_supplier_linked_to_parent_category_matches_child_product(): void
    {
        $parent = Category::query()->create([
            'slug' => 'boom-root',
            'status' => 'active',
            'name_en' => 'Boom',
            'name_ur' => 'Boom',
        ]);
        $child = Category::query()->create([
            'parent_id' => $parent->id,
            'slug' => 'boom-filters',
            'status' => 'active',
            'name_en' => 'Boom Filters',
            'name_ur' => 'Boom Filters',
        ]);

        $product = Product::query()->create([
            'category_id' => $child->id,
            'slug' => 'boom-mesh',
            'code' => 'MESH-1',
            'status' => 'active',
            'name_en' => 'Boom Missile Filter Mesh',
            'name_ur' => 'Boom Missile Filter Mesh',
        ]);

        $supplier = Supplier::query()->create([
            'supplier_code' => 'SUP-BOOM1',
            'status' => 'active',
            'business_name_en' => 'Toor Hardware Boom',
            'business_name_ur' => 'Toor Hardware Boom',
            'email' => 'boom@test.test',
            'address_en' => '1 Street',
            'city_en' => 'Lahore',
            'opening_balance' => 0,
            'contacts' => [['mobile_number' => '03006949038', 'contact_name_en' => 'Toor']],
        ]);
        $supplier->categories()->sync([$parent->id]);

        $order = $this->makeQueuedOrder('ONL-BOOM-1', [
            ['product' => $product, 'name' => 'Boom Missile Filter Mesh', 'qty' => 20, 'price' => 100],
        ]);

        $matched = app(OnlineOrderDispatchService::class)
            ->suggestSuppliersForItemIds($order->fresh(['items.product.category.parent']), [(int) $order->items->first()->id]);

        $this->assertTrue($matched->contains(fn (Supplier $row): bool => $row->id === $supplier->id));
    }

    public function test_packing_slip_is_clean_items_only(): void
    {
        [$admin, $order] = $this->seedForwardScenario();

        $this->actingAs($admin)
            ->get(route('admin.online-sale-orders.packing-slip', [
                'order' => $order,
                'autoprint' => 0,
            ]))
            ->assertOk()
            ->assertSee(__('order_dispatch.packing_slip.heading'), false)
            ->assertSee($order->order_number, false)
            ->assertSee('Boom Sprayer', false)
            ->assertSee(__('pos.print.layout_a4'), false)
            ->assertSee('Receipt', false)
            ->assertSee(__('order_dispatch.packing_slip.download_pdf'), false)
            ->assertDontSee('Forwarded', false)
            ->assertDontSee('forwarded', false)
            ->assertDontSee(__('pos.fields.unit_price'), false)
            ->assertDontSee('Forward Buyer', false);
    }

    public function test_packing_slip_pdf_download_returns_attachment(): void
    {
        [$admin, $order] = $this->seedForwardScenario();

        $response = $this->actingAs($admin)
            ->get(route('admin.online-sale-orders.packing-slip', [
                'order' => $order,
                'download' => 'pdf',
                'locale' => 'en',
                'autoprint' => 0,
            ]));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'packing-list-'.$order->order_number.'.pdf',
            (string) $response->headers->get('content-disposition'),
        );
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_inbox_print_actions_include_invoice_and_packing(): void
    {
        [$admin, $order] = $this->seedForwardScenario();
        $staff = $this->makeStaff($admin, 'Print Staff', 'print-staff@test.test');

        $thread = app(StaffMessagingService::class)
            ->forwardOrderToStaff($order, $staff, $admin, 'invoice');

        Livewire::actingAs($staff)
            ->test(ListInbox::class)
            ->call('openThread', $thread->id)
            ->assertSee(__('staff_inbox.list.print_invoice'), false)
            ->assertSee(__('staff_inbox.list.print_packing'), false)
            ->assertSee(__('pos.print.heading'), false);
    }

    public function test_staff_inbox_marks_thread_read_and_clears_unread(): void
    {
        [$admin, $order] = $this->seedForwardScenario();
        $staff = $this->makeStaff($admin, 'Purchaser Three', 'purchaser-three@test.test');

        $thread = app(StaffMessagingService::class)
            ->forwardOrderToStaff($order, $staff, $admin, 'invoice');

        $this->assertSame(1, app(StaffMessagingService::class)->unreadCountFor($staff));

        Livewire::actingAs($staff)
            ->test(ListInbox::class)
            ->call('openThread', $thread->id)
            ->assertSee($order->order_number, false);

        $this->assertSame(0, app(StaffMessagingService::class)->unreadCountFor($staff->fresh()));
    }

    /**
     * @return array{0: User, 1: OnlineSaleOrder, 2: Supplier, 3: Supplier}
     */
    private function seedForwardScenario(): array
    {
        $admin = $this->superAdmin();

        $category = Category::query()->create([
            'slug' => 'agriculture-machinery',
            'status' => 'active',
            'name_en' => 'Agriculture Machinery',
            'name_ur' => 'Agriculture Machinery',
        ]);

        $otherCategory = Category::query()->create([
            'slug' => 'unrelated-parts',
            'status' => 'active',
            'name_en' => 'Unrelated Parts',
            'name_ur' => 'Unrelated Parts',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'boom-sprayer',
            'code' => 'BOOM-1',
            'status' => 'active',
            'name_en' => 'Boom Sprayer',
            'name_ur' => 'Boom Sprayer',
        ]);

        $matched = Supplier::query()->create([
            'supplier_code' => 'SUP-TOR1',
            'status' => 'active',
            'business_name_en' => 'Tor Hardware',
            'business_name_ur' => 'Tor Hardware',
            'email' => 'tor@test.test',
            'address_en' => '1 Test Street',
            'city_en' => 'Lahore',
            'opening_balance' => 0,
            'contacts' => [
                ['mobile_number' => '03001112233', 'contact_name_en' => 'Tor'],
            ],
        ]);
        $matched->categories()->sync([$category->id]);

        $other = Supplier::query()->create([
            'supplier_code' => 'SUP-OTH1',
            'status' => 'active',
            'business_name_en' => 'Unrelated Supply Co',
            'business_name_ur' => 'Unrelated Supply Co',
            'email' => 'other@test.test',
            'address_en' => '2 Test Street',
            'city_en' => 'Lahore',
            'opening_balance' => 0,
            'contacts' => [
                ['mobile_number' => '03004445566', 'contact_name_en' => 'Other'],
            ],
        ]);
        $other->categories()->sync([$otherCategory->id]);

        $order = $this->makeQueuedOrder('ONL-FWD-1001', [
            ['product' => $product, 'name' => 'Boom Sprayer', 'qty' => 2, 'price' => 250],
        ]);

        return [$admin, $order->fresh('items'), $matched, $other];
    }

    /**
     * @param  list<array{product: Product, name: string, qty: float|int, price: float|int}>  $lines
     */
    private function makeQueuedOrder(string $number, array $lines): OnlineSaleOrder
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-'.substr(md5($number), 0, 8),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Forward',
            'last_name' => 'Buyer',
            'email' => strtolower($number).'@test.test',
            'phone' => '03001234567',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $total = collect($lines)->sum(fn (array $line): float => (float) $line['qty'] * (float) $line['price']);

        $sale = Sale::query()->create([
            'sale_number' => 'INV-'.substr($number, -4),
            'customer_id' => $customer->id,
            'status' => Sale::STATUS_COMPLETED,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'subtotal' => $total,
            'total_amount' => $total,
            'amount_paid' => $total,
            'sale_date' => now()->toDateString(),
        ]);

        $order = OnlineSaleOrder::query()->create([
            'order_number' => $number,
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'order_date' => now()->toDateString(),
            'locale' => 'en',
            'order_status' => OnlineSaleOrder::STATUS_CONVERTED,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            'payment_status' => 'paid',
            'subtotal' => $total,
            'total_amount' => $total,
            'amount_paid' => $total,
            'ship_contact_name' => 'Forward Buyer',
            'ship_phone' => '03001234567',
            'ship_address' => '1 Test Street',
            'ship_city' => 'Lahore',
            'converted_at' => now(),
            'queued_at' => now(),
            'queue_document' => OnlineSaleOrder::QUEUE_DOCUMENT_INVOICE,
        ]);

        foreach ($lines as $index => $line) {
            OnlineSaleOrderItem::query()->create([
                'online_sale_order_id' => $order->id,
                'product_id' => $line['product']->id,
                'sort_order' => $index + 1,
                'product_name_en' => $line['name'],
                'quantity' => $line['qty'],
                'unit_price' => $line['price'],
                'line_total' => (float) $line['qty'] * (float) $line['price'],
            ]);
        }

        return $order;
    }

    private function makeStaff(User $admin, string $name, string $email): User
    {
        return User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => $name,
            'email' => $email,
            'phone_numbers' => [[
                'contact_person' => $name,
                'contact_person_ur' => $name,
                'phone_number' => '03009998877',
            ]],
        ]);
    }

    private function superAdmin(): User
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Admin Forward',
            'email' => 'admin-forward@test.test',
            'phone_numbers' => [
                [
                    'contact_person' => 'Admin Forward',
                    'contact_person_ur' => 'Admin Forward',
                    'phone_number' => '03005556677',
                ],
            ],
        ]);
    }
}
