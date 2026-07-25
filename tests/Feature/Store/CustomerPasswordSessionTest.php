<?php

namespace Tests\Feature\Store;

use App\Models\Customer;
use App\Services\Access\SessionInvalidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerPasswordSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.driver' => 'database', 'session.encrypt' => false]);
    }

    public function test_password_change_invalidates_other_customer_sessions(): void
    {
        if (! Schema::hasTable('sessions')) {
            $this->markTestSkipped('sessions table missing.');
        }

        $customer = $this->makeCustomer('session-a@test.test', 'OldPass123');

        DB::table('sessions')->insert([
            'id' => 'other-customer-session',
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => base64_encode(serialize([
                'login_customer_'.sha1('customer') => $customer->id,
                '_token' => 'x',
            ])),
            'last_activity' => time(),
        ]);

        $this->actingAs($customer, 'customer')
            ->post(route('store.customer.account.password'), [
                'current_password' => 'OldPass123',
                'password' => 'NewPass456',
                'password_confirmation' => 'NewPass456',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => 'other-customer-session']);
    }

    public function test_customer_password_reset_tokens_use_dedicated_table(): void
    {
        $this->assertSame(
            'customer_password_reset_tokens',
            (string) config('auth.passwords.customers.table'),
        );
        $this->assertTrue(Schema::hasTable('customer_password_reset_tokens'));
    }

    public function test_wipe_all_for_customer_removes_payload_sessions(): void
    {
        if (! Schema::hasTable('sessions')) {
            $this->markTestSkipped('sessions table missing.');
        }

        $customer = $this->makeCustomer('session-b@test.test', 'OldPass123');

        DB::table('sessions')->insert([
            'id' => 'reset-customer-session',
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => base64_encode(serialize([
                'login_customer_abc' => $customer->id,
            ])),
            'last_activity' => time(),
        ]);

        app(SessionInvalidationService::class)->wipeAllForCustomer($customer);

        $this->assertDatabaseMissing('sessions', ['id' => 'reset-customer-session']);
    }

    private function makeCustomer(string $email, string $password): Customer
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
            'is_active' => true,
        ]);
    }
}
