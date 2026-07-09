<?php

namespace Tests\Feature\Store;

use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CheckoutSuccessAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_checkout_success_for_existing_order(): void
    {
        $order = $this->createOrderForCustomer($this->createCustomer('a@test.test'));

        $this->get(route('store.checkout.success', ['order' => $order->order_number]))
            ->assertNotFound();
    }

    public function test_customer_cannot_view_another_customers_checkout_success(): void
    {
        $owner = $this->createCustomer('owner@test.test');
        $other = $this->createCustomer('other@test.test');
        $order = $this->createOrderForCustomer($owner);

        $this->actingAs($other, 'customer')
            ->get(route('store.checkout.success', ['order' => $order->order_number]))
            ->assertNotFound();
    }

    public function test_customer_can_view_own_checkout_success(): void
    {
        $customer = $this->createCustomer('owner@test.test');
        $order = $this->createOrderForCustomer($customer);

        $this->actingAs($customer, 'customer')
            ->get(route('store.checkout.success', ['order' => $order->order_number]))
            ->assertOk()
            ->assertSee($order->order_number, false);
    }

    public function test_one_time_session_allows_success_before_login_required_on_refresh(): void
    {
        $customer = $this->createCustomer('owner@test.test');
        $order = $this->createOrderForCustomer($customer);

        $this->withSession(['placed_order_number' => $order->order_number])
            ->get(route('store.checkout.success', ['order' => $order->order_number]))
            ->assertOk();

        $this->get(route('store.checkout.success', ['order' => $order->order_number]))
            ->assertNotFound();
    }

    public function test_unknown_order_number_returns_not_found(): void
    {
        $this->get(route('store.checkout.success', ['order' => 'ONL-99999999']))
            ->assertNotFound();
    }

    private function createCustomer(string $email): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'CUS-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'phone' => '03001234567',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);
    }

    private function createOrderForCustomer(Customer $customer): OnlineSaleOrder
    {
        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'ONL-TEST-' . random_int(1000, 9999),
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'locale' => 'en',
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            'subtotal' => 100,
            'total_amount' => 100,
            'amount_paid' => 0,
            'ship_contact_name' => 'Test User',
            'ship_phone' => '03001234567',
        ]);

        OnlineSaleOrderItem::query()->create([
            'online_sale_order_id' => $order->id,
            'sort_order' => 1,
            'product_name_en' => 'Sample product',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        return $order;
    }
}
