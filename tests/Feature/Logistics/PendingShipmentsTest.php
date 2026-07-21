<?php

namespace Tests\Feature\Logistics;

use App\Livewire\Logistics\ListPendingShipments;
use App\Livewire\OnlineSaleOrders\ListOnlineSaleOrders;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use App\Models\OnlineSaleOrderSupplierAssignment;
use App\Models\Role;
use App\Models\User;
use App\Services\Logistics\LogisticsOverviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PendingShipmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_queued_invoice_appears_on_pending_shipments_list(): void
    {
        $admin = $this->superAdmin();
        $invoice = $this->makeInvoice('ONL-LOG-PEND');

        Livewire::actingAs($admin)
            ->test(ListPendingShipments::class)
            ->assertDontSee('ONL-LOG-PEND', false);

        Livewire::actingAs($admin)
            ->test(ListOnlineSaleOrders::class)
            ->call('sendInvoiceToQueue', $invoice->id);

        Livewire::actingAs($admin)
            ->test(ListPendingShipments::class)
            ->assertSee('ONL-LOG-PEND', false)
            ->assertSee(__('order_dispatch.fulfillment_statuses.awaiting_assignment'), false);
    }

    public function test_delivered_orders_are_hidden_from_pending_shipments(): void
    {
        $admin = $this->superAdmin();
        $invoice = $this->makeInvoice('ONL-LOG-DONE');
        $invoice->update([
            'queued_at' => now(),
            'queue_document' => OnlineSaleOrder::QUEUE_DOCUMENT_INVOICE,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_DELIVERED,
        ]);

        Livewire::actingAs($admin)
            ->test(ListPendingShipments::class)
            ->assertDontSee('ONL-LOG-DONE', false);
    }

    public function test_overview_includes_pending_shipments_count(): void
    {
        $this->makeInvoice('ONL-LOG-OV');
        OnlineSaleOrder::query()->where('order_number', 'ONL-LOG-OV')->update([
            'queued_at' => now(),
            'queue_document' => OnlineSaleOrder::QUEUE_DOCUMENT_INVOICE,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_SOURCING,
        ]);

        $cards = app(LogisticsOverviewService::class)->statCards();
        $pending = collect($cards)->firstWhere('key', 'pending_shipments');

        $this->assertNotNull($pending);
        $this->assertSame(1, $pending['count']);
    }

    public function test_customer_order_page_shows_fulfillment_and_tracking(): void
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-LOG-1',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Log',
            'last_name' => 'Buyer',
            'email' => 'log-buyer@test.test',
            'phone' => '03007654321',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $supplier = \App\Models\Supplier::query()->create([
            'supplier_code' => 'SUP-LOG1',
            'status' => 'active',
            'business_name_en' => 'Log Supplier',
            'business_name_ur' => 'Log Supplier',
            'email' => 'log-sup@test.test',
            'address_en' => '1 Street',
            'city_en' => 'Lahore',
            'opening_balance' => 0,
            'contacts' => [
                ['mobile_number' => '03001112233', 'contact_name_en' => 'Log'],
            ],
        ]);

        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'ONL-CUS-TRACK',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'locale' => 'en',
            'order_status' => OnlineSaleOrder::STATUS_CONVERTED,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_TRANSIT,
            'payment_status' => 'paid',
            'subtotal' => 100,
            'total_amount' => 100,
            'amount_paid' => 100,
            'ship_contact_name' => 'Log Buyer',
            'ship_phone' => '03007654321',
            'converted_at' => now(),
            'queued_at' => now(),
            'queue_document' => OnlineSaleOrder::QUEUE_DOCUMENT_INVOICE,
        ]);

        OnlineSaleOrderItem::query()->create([
            'online_sale_order_id' => $order->id,
            'sort_order' => 1,
            'product_name_en' => 'Seed Kit',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        OnlineSaleOrderSupplierAssignment::query()->create([
            'online_sale_order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'status' => OnlineSaleOrderSupplierAssignment::STATUS_SHIPPED,
            'carrier_name' => 'TCS',
            'tracking_number' => 'TCS123',
            'shipping_reference' => 'BILTY-9',
            'shipped_at' => now(),
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.orders.show', $order->order_number))
            ->assertOk()
            ->assertSee(__('order_dispatch.fulfillment_statuses.in_transit'), false)
            ->assertSee('TCS', false)
            ->assertSee('TCS123', false)
            ->assertSee('BILTY-9', false)
            ->assertDontSee('Log Supplier', false);
    }

    private function makeInvoice(string $number): OnlineSaleOrder
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-' . substr(md5($number), 0, 8),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Queue',
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

        $order = OnlineSaleOrder::query()->create([
            'order_number' => $number,
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'locale' => 'en',
            'order_status' => OnlineSaleOrder::STATUS_CONVERTED,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            'payment_status' => 'paid',
            'subtotal' => 50,
            'total_amount' => 50,
            'amount_paid' => 50,
            'ship_contact_name' => 'Queue Buyer',
            'ship_phone' => '03001234567',
            'converted_at' => now(),
            'queued_at' => null,
        ]);

        OnlineSaleOrderItem::query()->create([
            'online_sale_order_id' => $order->id,
            'sort_order' => 1,
            'product_name_en' => 'Item',
            'quantity' => 1,
            'unit_price' => 50,
            'line_total' => 50,
        ]);

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
            'email' => 'logistics-pending@test.test',
        ]);
    }
}
