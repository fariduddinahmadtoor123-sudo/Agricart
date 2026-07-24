<?php

namespace Tests\Feature\Store;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerPasswordAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_accepts_email_case_insensitively(): void
    {
        $customer = $this->makeCustomer('Case.User@Example.com', 'SecretPass1');

        $this->post(route('store.customer.login.submit'), [
            'login' => 'case.user@example.com',
            'login_password' => 'SecretPass1',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_wrong_current_password_does_not_change_password(): void
    {
        $customer = $this->makeCustomer('pwd@test.test', 'OldPass123');

        $this->actingAs($customer, 'customer')
            ->from(route('store.customer.account'))
            ->post(route('store.customer.account.password'), [
                'current_password' => 'WrongPass999',
                'password' => 'NewPass1234',
                'password_confirmation' => 'NewPass1234',
            ])
            ->assertRedirect(route('store.customer.account'))
            ->assertSessionHasErrors('current_password')
            ->assertSessionHas('customer_auth_status_type', 'danger');

        $customer->refresh();
        $this->assertTrue(Hash::check('OldPass123', $customer->getAuthPassword()));
        $this->assertFalse(Hash::check('NewPass1234', $customer->getAuthPassword()));
    }

    public function test_correct_current_password_updates_password(): void
    {
        $customer = $this->makeCustomer('pwd2@test.test', 'OldPass123');

        $this->actingAs($customer, 'customer')
            ->post(route('store.customer.account.password'), [
                'current_password' => 'OldPass123',
                'password' => 'NewPass1234',
                'password_confirmation' => 'NewPass1234',
            ])
            ->assertRedirect(route('store.customer.account'))
            ->assertSessionHas('customer_auth_status_type', 'success');

        $customer->refresh();
        $this->assertTrue(Hash::check('NewPass1234', $customer->getAuthPassword()));
        $this->assertFalse(Hash::check('OldPass123', $customer->getAuthPassword()));
    }

    public function test_inactive_customer_cannot_login(): void
    {
        $this->makeCustomer('off@test.test', 'SecretPass1', active: false);

        $this->post(route('store.customer.login.submit'), [
            'login' => 'off@test.test',
            'login_password' => 'SecretPass1',
        ])->assertRedirect(route('store.customer.login'))
            ->assertSessionHas('customer_auth_status_type', 'danger');

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
            'password' => $password,
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => $active,
        ]);
    }
}
