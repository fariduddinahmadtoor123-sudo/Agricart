<?php

namespace Tests\Feature\Auth;

use App\Filament\Auth\Notifications\ResetPassword as StaffResetPasswordNotification;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\Store\CustomerResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetLinkUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_reset_notification_uses_admin_reset_url_not_homepage(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'staff-reset@agricart.test',
            'status' => User::STATUS_ACTIVE,
        ]);

        $user->sendPasswordResetNotification('test-token-staff');

        Notification::assertSentTo($user, StaffResetPasswordNotification::class, function (StaffResetPasswordNotification $notification) use ($user): bool {
            $mail = $notification->toMail($user);
            $url = (string) ($mail->actionUrl ?? '');

            return str_contains($url, '/admin/password-reset/reset')
                && str_contains($url, 'token=test-token-staff')
                && ! preg_match('#https?://[^/]+/?$#', $url);
        });
    }

    public function test_customer_reset_notification_uses_store_reset_url(): void
    {
        Notification::fake();

        $customer = $this->makeCustomer('customer-reset@agricart.test');

        $customer->sendPasswordResetNotification('test-token-customer');

        Notification::assertSentTo($customer, CustomerResetPassword::class, function (CustomerResetPassword $notification) use ($customer): bool {
            $mail = $notification->toMail($customer);
            $url = (string) ($mail->actionUrl ?? '');

            return str_contains($url, '/reset-password/test-token-customer')
                && str_contains($url, 'email=')
                && ! str_contains($url, '/admin/');
        });
    }

    public function test_same_email_staff_and_customer_get_separate_reset_urls(): void
    {
        Notification::fake();

        $email = 'shared-role@agricart.test';

        $user = User::factory()->create([
            'email' => $email,
            'status' => User::STATUS_ACTIVE,
        ]);
        $customer = $this->makeCustomer($email);

        Password::broker('users')->sendResetLink(['email' => $email]);
        Password::broker('customers')->sendResetLink(['email' => $email]);

        Notification::assertSentTo($user, StaffResetPasswordNotification::class, function (StaffResetPasswordNotification $notification) use ($user): bool {
            $url = (string) ($notification->toMail($user)->actionUrl ?? '');

            return str_contains($url, '/admin/password-reset/reset');
        });

        Notification::assertSentTo($customer, CustomerResetPassword::class, function (CustomerResetPassword $notification) use ($customer): bool {
            $url = (string) ($notification->toMail($customer)->actionUrl ?? '');

            return str_contains($url, '/reset-password/')
                && ! str_contains($url, '/admin/');
        });
    }

    private function makeCustomer(string $email): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'CUS-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Reset',
            'last_name' => 'Customer',
            'email' => $email,
            'phone' => '03'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'password' => 'CustomerPass123',
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);
    }
}
