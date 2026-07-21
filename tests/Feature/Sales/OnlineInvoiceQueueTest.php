<?php

namespace Tests\Feature\Sales;

use App\Livewire\OnlineSaleOrders\ListOnlineSaleOrders;
use App\Livewire\OnlineSaleOrders\ListOrderDispatch;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use App\Models\Role;
use App\Models\User;
use App\Services\Sales\OnlineSaleOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class OnlineInvoiceQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_to_queue_moves_invoice_to_dispatch_list(): void
    {
        $admin = $this->superAdmin();
        $invoice = $this->makeInvoice('ONL-Q-SEND');

        Livewire::actingAs($admin)
            ->test(ListOnlineSaleOrders::class)
            ->assertSee('ONL-Q-SEND', false)
            ->assertSee(__('online_sale_orders.actions.send_to_queue'), false)
            ->call('sendToQueue', $invoice->id);

        $invoice->refresh();
        $this->assertNotNull($invoice->queued_at);

        Livewire::actingAs($admin)
            ->test(ListOnlineSaleOrders::class)
            ->assertSee(__('online_sale_orders.queue_statuses.queued'), false)
            ->assertDontSeeHtml('wire:click="sendToQueue(');

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->assertSee('ONL-Q-SEND', false)
            ->assertSee(__('online_sale_orders.actions.forward_short'), false);
    }

    public function test_dispatch_list_excludes_unqueued_invoices(): void
    {
        $admin = $this->superAdmin();
        $this->makeInvoice('ONL-Q-HOLD');

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->assertDontSee('ONL-Q-HOLD', false);
    }

    public function test_send_to_queue_rejects_quotation(): void
    {
        $admin = $this->superAdmin();
        $quotation = $this->makeInvoice('ONL-Q-QUOTE');
        $quotation->update([
            'order_status' => OnlineSaleOrder::STATUS_PAYMENT_RECEIVED,
            'converted_at' => null,
        ]);

        $this->expectException(ValidationException::class);

        app(OnlineSaleOrderService::class)->sendToDispatchQueue($quotation->fresh());
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
            'subtotal' => 100,
            'total_amount' => 100,
            'amount_paid' => 100,
            'ship_contact_name' => 'Queue Buyer',
            'ship_phone' => '03001234567',
            'converted_at' => now(),
            'queued_at' => null,
        ]);

        OnlineSaleOrderItem::query()->create([
            'online_sale_order_id' => $order->id,
            'sort_order' => 1,
            'product_name_en' => 'Sample',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
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
            'name' => 'Admin Queue',
            'email' => 'admin-queue@test.test',
        ]);
    }
}
