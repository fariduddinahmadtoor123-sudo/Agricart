<?php

namespace Tests\Feature\Sales;

use App\Livewire\OnlineSaleOrders\ListOrderDispatch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use App\Models\OnlineSaleOrderSupplierAssignment;
use App\Models\OnlineSaleOrderSupplierAssignmentItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Sales\OnlineOrderDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class OnlineSaleOrderForwardTest extends TestCase
{
    use RefreshDatabase;

    public function test_forward_panel_lists_category_suppliers_and_excludes_unrelated(): void
    {
        [$admin, $order] = $this->seedForwardScenario();

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->call('openForward', $order->id)
            ->assertSet('forwardOrderId', $order->id)
            ->assertSee('Tor Hardware', false)
            ->assertSee(__('online_sale_orders.forward.matched_suppliers_heading'), false)
            ->assertSee(__('online_sale_orders.forward.other_suppliers_heading'), false)
            ->assertSee('Unrelated Supply Co', false)
            ->set('forwardDocument', 'invoice')
            ->assertSee($admin->name, false)
            ->assertDontSee('Tor Hardware', false);
    }

    public function test_forward_whatsapp_uses_supplier_primary_mobile_and_marks_sent(): void
    {
        [$admin, $order, $matched] = $this->seedForwardScenario();
        $itemId = (int) $order->items->first()->id;

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->call('openForward', $order->id)
            ->set('forwardSelectedItemIds', [$itemId])
            ->call('forwardWhatsApp', 'supplier', $matched->id)
            ->assertDispatched('online-sale-orders-open-external');

        $assignment = OnlineSaleOrderSupplierAssignment::query()
            ->where('online_sale_order_id', $order->id)
            ->where('supplier_id', $matched->id)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertSame(OnlineSaleOrderSupplierAssignment::STATUS_SENT, $assignment->status);
        $this->assertTrue(
            OnlineSaleOrderSupplierAssignmentItem::query()
                ->where('online_sale_order_supplier_assignment_id', $assignment->id)
                ->where('online_sale_order_item_id', $itemId)
                ->exists()
        );

        $url = app(OnlineOrderDispatchService::class)->whatsAppUrlForSupplier(
            $order->fresh('items'),
            $matched,
            'packing',
            [$itemId],
        );
        $this->assertNotNull($url);
        $this->assertStringContainsString('api.whatsapp.com/send?phone=923001112233', $url);
        $this->assertStringContainsString(rawurlencode('Tor Hardware'), $url);
    }

    public function test_partial_forward_keeps_order_in_queue_with_remaining_lines(): void
    {
        [$admin, $order, $agriSupplier, $toolSupplier] = $this->seedMultiCategoryScenario();
        $items = $order->items->values();
        $agriItemId = (int) $items[0]->id;
        $toolItemId = (int) $items[1]->id;

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->call('openForward', $order->id)
            ->set('forwardSelectedItemIds', [$agriItemId])
            ->call('forwardWhatsApp', 'supplier', $agriSupplier->id)
            ->assertSee(__('online_sale_orders.forward.item_sent_to', ['supplier' => 'Agri Supply']), false)
            ->assertSee(__('online_sale_orders.forward.item_remaining'), false);

        $order->refresh();
        $this->assertNotNull($order->queued_at);

        $progress = app(OnlineOrderDispatchService::class)->packingProgress($order);
        $this->assertSame(1, $progress['sent']);
        $this->assertSame(1, $progress['remaining']);
        $this->assertFalse($progress['complete']);

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->assertSee($order->order_number, false)
            ->assertSee(__('order_dispatch.list.packing_progress', ['sent' => 1, 'total' => 2]), false)
            ->call('openForward', $order->id)
            ->set('forwardSelectedItemIds', [$toolItemId])
            ->assertSee('Puncture Tools Co', false)
            ->call('forwardWhatsApp', 'supplier', $toolSupplier->id);

        $progress = app(OnlineOrderDispatchService::class)->packingProgress($order->fresh('items'));
        $this->assertTrue($progress['complete']);
        $this->assertNotNull($order->fresh()->queued_at);
    }

    public function test_packing_slip_shows_recipient_business_name(): void
    {
        [$admin, $order, $matched] = $this->seedForwardScenario();

        $this->actingAs($admin)
            ->get(route('admin.online-sale-orders.packing-slip', [
                'order' => $order,
                'supplier_id' => $matched->id,
                'autoprint' => 0,
            ]))
            ->assertOk()
            ->assertSee('To: Tor Hardware', false)
            ->assertSee($order->order_number, false);
    }

    public function test_forward_staff_whatsapp_uses_primary_phone(): void
    {
        [$admin, $order] = $this->seedForwardScenario();

        $staff = User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Packing Staff',
            'email' => 'packing-staff@test.test',
            'phone_numbers' => [
                [
                    'contact_person' => 'Packing Staff',
                    'contact_person_ur' => 'Packing Staff',
                    'phone_number' => '03009998877',
                ],
            ],
        ]);

        $url = app(OnlineOrderDispatchService::class)->whatsAppUrlForStaff($order->fresh('items'), $staff, 'invoice');

        $this->assertNotNull($url);
        $this->assertStringContainsString('api.whatsapp.com/send?phone=923009998877', $url);
        $this->assertStringContainsString(rawurlencode('Packing Staff'), $url);
        $this->assertStringContainsString(rawurlencode('Boom Sprayer'), $url);
        $this->assertStringContainsString(rawurlencode('× 2'), $url);
    }

    public function test_forward_email_without_email_does_not_mark_sent(): void
    {
        [$admin, $order, $matched] = $this->seedForwardScenario();
        $matched->update(['email' => null]);
        $itemId = (int) $order->items->first()->id;

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->call('openForward', $order->id)
            ->set('forwardSelectedItemIds', [$itemId])
            ->call('forwardEmail', 'supplier', $matched->id)
            ->assertSet('forwardStatusMessage', __('online_sale_orders.forward.email_missing'))
            ->assertNotDispatched('online-sale-orders-open-external');

        $this->assertFalse(
            OnlineSaleOrderSupplierAssignmentItem::query()
                ->where('online_sale_order_item_id', $itemId)
                ->exists()
        );
    }

    public function test_declined_assignment_lines_can_be_reforwarded_to_another_supplier(): void
    {
        [$admin, $order, $matched, $other] = $this->seedForwardScenario();
        $itemId = (int) $order->items->first()->id;
        $dispatch = app(OnlineOrderDispatchService::class);

        $dispatch->assignItemsToSupplierAndMarkSent($order, $matched->id, [$itemId], $admin);
        $dispatch->applySupplierFulfillmentUpdates($order->fresh('supplierAssignments'), [
            $matched->id => ['status' => OnlineSaleOrderSupplierAssignment::STATUS_DECLINED],
        ]);

        $this->assertFalse(
            OnlineSaleOrderSupplierAssignmentItem::query()
                ->where('online_sale_order_item_id', $itemId)
                ->exists()
        );

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->call('openForward', $order->id)
            ->set('forwardSelectedItemIds', [$itemId])
            ->call('forwardWhatsApp', 'supplier', $other->id)
            ->assertDispatched('online-sale-orders-open-external');

        $this->assertTrue(
            OnlineSaleOrderSupplierAssignmentItem::query()
                ->where('online_sale_order_item_id', $itemId)
                ->whereHas('assignment', fn ($q) => $q->where('supplier_id', $other->id))
                ->exists()
        );
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
     * @return array{0: User, 1: OnlineSaleOrder, 2: Supplier, 3: Supplier}
     */
    private function seedMultiCategoryScenario(): array
    {
        $admin = $this->superAdmin();

        $agriCat = Category::query()->create([
            'slug' => 'shindri-agri',
            'status' => 'active',
            'name_en' => 'Agriculture',
            'name_ur' => 'Agriculture',
        ]);
        $toolCat = Category::query()->create([
            'slug' => 'puncture-tools',
            'status' => 'active',
            'name_en' => 'Puncture Tools',
            'name_ur' => 'Puncture Tools',
        ]);

        $agriProduct = Product::query()->create([
            'category_id' => $agriCat->id,
            'slug' => 'shindri-kit',
            'code' => 'SHN-1',
            'status' => 'active',
            'name_en' => 'Shindri Kit',
            'name_ur' => 'Shindri Kit',
        ]);
        $toolProduct = Product::query()->create([
            'category_id' => $toolCat->id,
            'slug' => 'puncture-kit',
            'code' => 'PUN-1',
            'status' => 'active',
            'name_en' => 'Puncture Kit',
            'name_ur' => 'Puncture Kit',
        ]);

        $agriSupplier = Supplier::query()->create([
            'supplier_code' => 'SUP-AGRI1',
            'status' => 'active',
            'business_name_en' => 'Agri Supply',
            'business_name_ur' => 'Agri Supply',
            'email' => 'agri@test.test',
            'address_en' => 'Agri Street',
            'city_en' => 'Lahore',
            'opening_balance' => 0,
            'contacts' => [
                ['mobile_number' => '03001110001', 'contact_name_en' => 'Agri'],
            ],
        ]);
        $agriSupplier->categories()->sync([$agriCat->id]);

        $toolSupplier = Supplier::query()->create([
            'supplier_code' => 'SUP-TOOL1',
            'status' => 'active',
            'business_name_en' => 'Puncture Tools Co',
            'business_name_ur' => 'Puncture Tools Co',
            'email' => 'tools@test.test',
            'address_en' => 'Tools Street',
            'city_en' => 'Lahore',
            'opening_balance' => 0,
            'contacts' => [
                ['mobile_number' => '03001110002', 'contact_name_en' => 'Tools'],
            ],
        ]);
        $toolSupplier->categories()->sync([$toolCat->id]);

        $order = $this->makeQueuedOrder('ONL-FWD-MIX', [
            ['product' => $agriProduct, 'name' => 'Shindri Kit', 'qty' => 1, 'price' => 100],
            ['product' => $toolProduct, 'name' => 'Puncture Kit', 'qty' => 1, 'price' => 200],
        ]);

        return [$admin, $order->fresh('items'), $agriSupplier, $toolSupplier];
    }

    /**
     * @param  list<array{product: Product, name: string, qty: float|int, price: float|int}>  $lines
     */
    private function makeQueuedOrder(string $number, array $lines): OnlineSaleOrder
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-' . substr(md5($number), 0, 8),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Forward',
            'last_name' => 'Buyer',
            'email' => strtolower($number) . '@test.test',
            'phone' => '03001234567',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $total = collect($lines)->sum(fn (array $line): float => (float) $line['qty'] * (float) $line['price']);

        $order = OnlineSaleOrder::query()->create([
            'order_number' => $number,
            'customer_id' => $customer->id,
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
            'converted_at' => now(),
            'queued_at' => now(),
            'queue_document' => OnlineSaleOrder::QUEUE_DOCUMENT_PACKING_LIST,
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
