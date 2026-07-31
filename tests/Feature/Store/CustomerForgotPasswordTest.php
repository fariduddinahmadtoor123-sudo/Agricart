<?php

namespace Tests\Feature\Store;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CustomerForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_forgot_password_emails_customer_even_when_same_email_is_staff(): void
    {
        Notification::fake();

        $email = 'shared@agricart.test';

        User::factory()->create([
            'email' => $email,
            'password' => 'StaffPass123',
        ]);

        $customer = Customer::query()->create([
            'customer_number' => 'CUS-10001',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Shared',
            'last_name' => 'User',
            'email' => $email,
            'phone' => '03001234567',
            'password' => 'CustomerPass123',
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $this->from(route('store.customer.password.request'))
            ->post(route('store.customer.password.email'), ['email' => $email])
            ->assertRedirect()
            ->assertSessionHas('customer_auth_status_type', 'success');

        Notification::assertSentTo($customer, ResetPassword::class);
        Notification::assertCount(1);
    }

    public function test_store_forgot_password_shows_success_when_email_is_not_a_customer(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'staff-only@agricart.test',
            'password' => 'StaffPass123',
        ]);

        $this->from(route('store.customer.password.request'))
            ->post(route('store.customer.password.email'), ['email' => 'staff-only@agricart.test'])
            ->assertRedirect()
            ->assertSessionHas('customer_auth_status_type', 'success')
            ->assertSessionDoesntHaveErrors('email');

        Notification::assertNothingSent();
    }
}
