<?php

namespace Tests\Feature\Auth;

use App\Filament\Auth\Notifications\ResetPassword as StaffResetPasswordNotification;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as LaravelResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

        $customer = Customer::query()->create([
            'customer_number' => 'CUS-20001',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Reset',
            'last_name' => 'Customer',
            'email' => 'customer-reset@agricart.test',
            'phone' => '03009876543',
            'password' => 'CustomerPass123',
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $customer->sendPasswordResetNotification('test-token-customer');

        Notification::assertSentTo($customer, LaravelResetPassword::class, function (LaravelResetPassword $notification) use ($customer): bool {
            $mail = $notification->toMail($customer);
            $url = (string) ($mail->actionUrl ?? '');

            return str_contains($url, '/reset-password/test-token-customer')
                && str_contains($url, 'email=')
                && ! str_contains($url, '/admin/');
        });
    }
}
