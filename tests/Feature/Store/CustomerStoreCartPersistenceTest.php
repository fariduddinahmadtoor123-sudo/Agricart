<?php

namespace Tests\Feature\Store;

use App\Models\Customer;
use App\Models\CustomerStoreCart;
use App\Services\Store\StoreCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerStoreCartPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_survives_logout_and_login_for_same_customer(): void
    {
        $customer = $this->createCustomer('cart@test.test');
        $password = 'password';

        $this->actingAs($customer, 'customer');

        $cart = app(StoreCartService::class);
        $cart->mergeAfterCustomerLogin($customer->id, [
            ['product_id' => 42, 'quantity' => '2'],
        ]);

        $this->post(route('store.customer.logout'))->assertRedirect(route('store.home'));

        $this->assertDatabaseHas('customer_store_carts', [
            'customer_id' => $customer->id,
        ]);

        $row = CustomerStoreCart::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($row);
        $this->assertSame(42, (int) $row->items[0]['product_id']);

        $this->post(route('store.customer.login.submit'), [
            'login' => $customer->email,
            'login_password' => $password,
        ])->assertRedirect();

        $this->assertSame(2, app(StoreCartService::class)->totalQuantity());
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
}
