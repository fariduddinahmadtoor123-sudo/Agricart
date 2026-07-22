<?php

namespace Tests\Feature\Logistics;

use App\Livewire\Logistics\ListPendingShipments;
use App\Livewire\Logistics\ListShippedOrders;
use App\Livewire\Logistics\ShipForm;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use App\Models\OnlineSaleOrderSupplierAssignment;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Logistics\LogisticsOverviewService;
use App\Services\Sales\OnlineOrderDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class LogisticsShipHandoffTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_excludes_shipped_and_ship_notifies_customer(): void
    {
        Notification::fake();
        Mail::fake();

        $admin = $this->superAdmin();
        $order = $this->makeReleasedOrder('ONL-SHIP-1');

        Livewire::actingAs($admin)
            ->test(ListPendingShipments::class)
            ->assertSee('ONL-SHIP-1', false);

        Livewire::actingAs($admin)
            ->test(ListShippedOrders::class)
            ->assertDontSee('ONL-SHIP-1', false);

        Livewire::actingAs($admin)
            ->test(ShipForm::class)
            ->call('openForm', $order->id, 'edit')
            ->set('supplierParcels.'.$order->supplierAssignments->first()->supplier_id.'.carrier_name', 'TCS')
            ->set('supplierParcels.'.$order->supplierAssignments->first()->supplier_id.'.tracking_number', 'TCS-999')
            ->set('supplierParcels.'.$order->supplierAssignments->first()->supplier_id.'.shipping_reference', '')
            ->call('saveAndClose')
            ->assertHasNoErrors();

        $order->refresh();
        $assignment = $order->supplierAssignments()->first();

        $this->assertSame(OnlineSaleOrderSupplierAssignment::STATUS_SHIPPED, $assignment->status);
        $this->assertNotNull($assignment->shipped_at);
        $this->assertSame(OnlineSaleOrder::FULFILLMENT_TRANSIT, $order->fulfillment_status);
        $this->assertNotNull($order->shipping_customer_notified_at);

        Livewire::actingAs($admin)
            ->test(ListPendingShipments::class)
            ->assertDontSee('ONL-SHIP-1', false);

        Livewire::actingAs($admin)
            ->test(ListShippedOrders::class)
            ->assertSee('ONL-SHIP-1', false)
            ->assertSee('TCS-999', false);

        Notification::assertSentTo(
            $order->customer,
            \App\Notifications\Store\OnlineOrderShippedNotification::class,
        );
    }

    public function test_ship_requires_tracking_bilty_or_proof(): void
    {
        $admin = $this->superAdmin();
        $order = $this->makeReleasedOrder('ONL-SHIP-EMPTY');
        $supplierId = (int) $order->supplierAssignments->first()->supplier_id;

        Livewire::actingAs($admin)
            ->test(ShipForm::class)
            ->call('openForm', $order->id, 'edit')
            ->set('supplierParcels.'.$supplierId.'.carrier_name', 'Only Carrier')
            ->set('supplierParcels.'.$supplierId.'.tracking_number', '')
            ->set('supplierParcels.'.$supplierId.'.shipping_reference', '')
            ->call('saveAndClose')
            ->assertSet('statusType', 'danger');

        $this->assertNull($order->fresh()->supplierAssignments->first()->shipped_at);
    }

    public function test_overview_counts_pending_and_shipped(): void
    {
        Notification::fake();
        Mail::fake();

        $pending = $this->makeReleasedOrder('ONL-OV-PEND');
        $shipped = $this->makeReleasedOrder('ONL-OV-SHIP');

        app(OnlineOrderDispatchService::class)->shipLogisticsHandoff(
            $shipped,
            [
                (int) $shipped->supplierAssignments->first()->supplier_id => [
                    'carrier_name' => 'M&P',
                    'tracking_number' => 'MP-1',
                    'shipping_reference' => '',
                ],
            ],
        );

        $cards = collect(app(LogisticsOverviewService::class)->statCards());
        $this->assertSame(1, $cards->firstWhere('key', 'pending_shipments')['count']);
        $this->assertSame(1, $cards->firstWhere('key', 'shipped_orders')['count']);
        $this->assertSame('ONL-OV-PEND', $pending->order_number);
    }

    private function makeReleasedOrder(string $number): OnlineSaleOrder
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-'.substr(md5($number), 0, 8),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Ship',
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

        $supplier = Supplier::query()->create([
            'supplier_code' => 'SUP-'.substr(md5($number), 0, 6),
            'status' => 'active',
            'business_name_en' => 'Ship Supply',
            'business_name_ur' => 'Ship Supply',
            'email' => 'sup-'.strtolower($number).'@test.test',
            'address_en' => '1 Street',
            'city_en' => 'Lahore',
            'opening_balance' => 0,
            'contacts' => [['mobile_number' => '03001112233', 'contact_name_en' => 'S']],
        ]);

        $order = OnlineSaleOrder::query()->create([
            'order_number' => $number,
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'locale' => 'en',
            'order_status' => OnlineSaleOrder::STATUS_CONVERTED,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_READY,
            'payment_status' => 'paid',
            'subtotal' => 50,
            'total_amount' => 50,
            'amount_paid' => 50,
            'ship_contact_name' => 'Ship Buyer',
            'ship_phone' => '03001234567',
            'converted_at' => now(),
            'queued_at' => now(),
            'queue_document' => OnlineSaleOrder::QUEUE_DOCUMENT_INVOICE,
        ]);

        OnlineSaleOrderItem::query()->create([
            'online_sale_order_id' => $order->id,
            'sort_order' => 1,
            'product_name_en' => 'Item',
            'quantity' => 1,
            'unit_price' => 50,
            'line_total' => 50,
        ]);

        OnlineSaleOrderSupplierAssignment::query()->create([
            'online_sale_order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'status' => OnlineSaleOrderSupplierAssignment::STATUS_SENT,
            'sent_at' => now(),
            'released_at' => now(),
        ]);

        return $order->fresh(['supplierAssignments', 'customer']);
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
            'email' => 'logistics-ship-'.uniqid().'@test.test',
        ]);
    }
}
