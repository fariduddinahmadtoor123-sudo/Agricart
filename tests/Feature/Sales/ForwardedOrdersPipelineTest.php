<?php

namespace Tests\Feature\Sales;

use App\Livewire\Logistics\ListPendingShipments;
use App\Livewire\OnlineSaleOrders\ListForwardedOrders;
use App\Livewire\OnlineSaleOrders\ListOrderDispatch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use App\Models\OnlineSaleOrderStaffAssignment;
use App\Models\OnlineSaleOrderSupplierAssignment;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Logistics\LogisticsOverviewService;
use App\Services\Sales\OnlineOrderDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ForwardedOrdersPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_packing_complete_leaves_queue_and_appears_on_forwarded(): void
    {
        [$admin, $order, $supplier] = $this->seedPackingOrder();
        $itemId = (int) $order->items->first()->id;

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->assertSee($order->order_number, false)
            ->call('openForward', $order->id)
            ->set('forwardSelectedItemIds', [$itemId])
            ->call('forwardWhatsApp', 'supplier', $supplier->id);

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->assertDontSee($order->order_number, false);

        Livewire::actingAs($admin)
            ->test(ListForwardedOrders::class)
            ->assertSee('Tor Hardware', false)
            ->call('toggleGroup', 'supplier:'.$supplier->id)
            ->assertSee($order->order_number, false);
    }

    public function test_partial_forward_stays_on_queue_and_shows_on_forwarded(): void
    {
        [$admin, $order, $supplier] = $this->seedTwoLineOrder();
        $firstId = (int) $order->items->first()->id;

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->call('openForward', $order->id)
            ->set('forwardSelectedItemIds', [$firstId])
            ->call('forwardWhatsApp', 'supplier', $supplier->id);

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->assertSee($order->order_number, false);

        Livewire::actingAs($admin)
            ->test(ListForwardedOrders::class)
            ->assertSee('Tor Hardware', false);
    }

    public function test_mark_released_moves_to_logistics_pending(): void
    {
        [$admin, $order, $supplier] = $this->seedPackingOrder();
        $itemId = (int) $order->items->first()->id;
        $dispatch = app(OnlineOrderDispatchService::class);
        $assignment = $dispatch->assignItemsToSupplierAndMarkSent($order, $supplier->id, [$itemId], $admin);

        Livewire::actingAs($admin)
            ->test(ListPendingShipments::class)
            ->assertDontSee($order->order_number, false);

        Livewire::actingAs($admin)
            ->test(ListForwardedOrders::class)
            ->call('markReleased', 'supplier', $assignment->id);

        $assignment->refresh();
        $this->assertNotNull($assignment->released_at);
        $this->assertSame(OnlineSaleOrder::FULFILLMENT_READY, $order->fresh()->fulfillment_status);

        Livewire::actingAs($admin)
            ->test(ListForwardedOrders::class)
            ->assertDontSee($order->order_number, false);

        Livewire::actingAs($admin)
            ->test(ListPendingShipments::class)
            ->assertSee($order->order_number, false);

        $pending = collect(app(LogisticsOverviewService::class)->statCards())->firstWhere('key', 'pending_shipments');
        $this->assertSame(1, $pending['count']);
    }

    public function test_staff_invoice_forward_creates_assignment_and_leaves_invoice_queue(): void
    {
        [$admin, $order] = $this->seedInvoiceOrder();
        $staff = User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Invoice Staff',
            'email' => 'invoice-staff@test.test',
            'phone_numbers' => [[
                'contact_person' => 'Invoice Staff',
                'contact_person_ur' => 'Invoice Staff',
                'phone_number' => '03009998877',
            ]],
        ]);

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->call('openForward', $order->id)
            ->set('forwardDocument', 'invoice')
            ->call('forwardWhatsApp', 'staff', $staff->id)
            ->assertDispatched('online-sale-orders-open-external');

        $this->assertTrue(
            OnlineSaleOrderStaffAssignment::query()
                ->where('online_sale_order_id', $order->id)
                ->where('user_id', $staff->id)
                ->whereNotNull('sent_at')
                ->whereNull('released_at')
                ->exists()
        );

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->assertDontSee($order->order_number, false);

        Livewire::actingAs($admin)
            ->test(ListForwardedOrders::class)
            ->assertSee('Invoice Staff', false)
            ->call('toggleGroup', 'staff:'.$staff->id)
            ->assertSee($order->order_number, false);
    }

    /**
     * @return array{0: User, 1: OnlineSaleOrder, 2: Supplier}
     */
    private function seedPackingOrder(): array
    {
        return $this->seedOrder('ONL-FWD-OUT-1', OnlineSaleOrder::QUEUE_DOCUMENT_PACKING_LIST, 1);
    }

    /**
     * @return array{0: User, 1: OnlineSaleOrder, 2: Supplier}
     */
    private function seedTwoLineOrder(): array
    {
        return $this->seedOrder('ONL-FWD-OUT-2', OnlineSaleOrder::QUEUE_DOCUMENT_PACKING_LIST, 2);
    }

    /**
     * @return array{0: User, 1: OnlineSaleOrder}
     */
    private function seedInvoiceOrder(): array
    {
        [$admin, $order] = $this->seedOrder('ONL-FWD-INV-1', OnlineSaleOrder::QUEUE_DOCUMENT_INVOICE, 1);

        return [$admin, $order];
    }

    /**
     * @return array{0: User, 1: OnlineSaleOrder, 2: Supplier}
     */
    private function seedOrder(string $number, string $queueDocument, int $lines): array
    {
        $admin = $this->superAdmin();

        $category = Category::query()->create([
            'slug' => 'fwd-cat-'.substr(md5($number), 0, 6),
            'status' => 'active',
            'name_en' => 'Agriculture',
            'name_ur' => 'Agriculture',
        ]);

        $supplier = Supplier::query()->create([
            'supplier_code' => 'SUP-'.substr(md5($number), 0, 6),
            'status' => 'active',
            'business_name_en' => 'Tor Hardware',
            'business_name_ur' => 'Tor Hardware',
            'email' => 'tor-'.$number.'@test.test',
            'address_en' => '1 Test Street',
            'city_en' => 'Lahore',
            'opening_balance' => 0,
            'contacts' => [
                ['mobile_number' => '03001112233', 'contact_name_en' => 'Tor'],
            ],
        ]);
        $supplier->categories()->sync([$category->id]);

        $customer = Customer::query()->create([
            'customer_number' => 'CUS-'.substr(md5($number), 0, 8),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Outbox',
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

        $order = OnlineSaleOrder::query()->create([
            'order_number' => $number,
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'locale' => 'en',
            'order_status' => OnlineSaleOrder::STATUS_CONVERTED,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            'payment_status' => 'paid',
            'subtotal' => 100 * $lines,
            'total_amount' => 100 * $lines,
            'amount_paid' => 100 * $lines,
            'ship_contact_name' => 'Outbox Buyer',
            'ship_phone' => '03001234567',
            'converted_at' => now(),
            'queued_at' => now(),
            'queue_document' => $queueDocument,
        ]);

        for ($i = 0; $i < $lines; $i++) {
            $product = Product::query()->create([
                'category_id' => $category->id,
                'slug' => 'prod-'.$number.'-'.$i,
                'code' => 'P-'.$number.'-'.$i,
                'status' => 'active',
                'name_en' => 'Item '.$i,
                'name_ur' => 'Item '.$i,
            ]);

            OnlineSaleOrderItem::query()->create([
                'online_sale_order_id' => $order->id,
                'product_id' => $product->id,
                'sort_order' => $i + 1,
                'product_name_en' => 'Item '.$i,
                'quantity' => 1,
                'unit_price' => 100,
                'line_total' => 100,
            ]);
        }

        return [$admin, $order->fresh('items'), $supplier];
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
            'email' => 'fwd-pipeline@test.test',
            'phone_numbers' => [[
                'contact_person' => 'Admin',
                'contact_person_ur' => 'Admin',
                'phone_number' => '03005556677',
            ]],
        ]);
    }
}
