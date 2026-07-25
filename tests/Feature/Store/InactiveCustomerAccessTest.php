<?php

namespace Tests\Feature\Store;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InactiveCustomerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivated_customer_is_logged_out_from_account_routes(): void
    {
        $customer = $this->makeCustomer('deactivated@test.test', 'SecretPass1');

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.account'))
            ->assertOk();

        $customer->forceFill(['is_active' => false])->save();

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.account'))
            ->assertRedirect(route('store.customer.login'))
            ->assertSessionHas('customer_auth_status_type', 'danger')
            ->assertSessionHas(
                'customer_auth_status',
                __('agricart.customer.auth.session_deactivated'),
            );

        $this->assertGuest('customer');
    }

    private function makeCustomer(string $email, string $password, bool $active = true): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'CUS-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'phone' => '03'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'password' => Hash::make($password),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => $active,
        ]);
    }
}
