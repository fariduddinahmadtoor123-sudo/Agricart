<?php

namespace Tests\Feature\Contacts;

use App\Models\Customer;
use App\Services\Contacts\CustomerPortableArchiveService;
use App\Services\Contacts\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerPortableArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_filename_includes_entity_scope_and_date(): void
    {
        Storage::fake('public');

        $this->makeOfflineCustomer('CUS-10001', 'Offline One', '03001110001');
        $this->makeOnlineCustomer('CUS-10002', 'Online One', 'online1@example.com', '03001110002');

        $service = app(CustomerPortableArchiveService::class);

        $all = $service->export(CustomerPortableArchiveService::SCOPE_ALL);
        $this->assertSame(2, $all['count']);
        $this->assertSame('customers-'.now()->format('Y-m-d').'.ags', $all['filename']);

        $online = $service->export(CustomerPortableArchiveService::SCOPE_ONLINE);
        $this->assertSame(1, $online['count']);
        $this->assertSame('customers-online-'.now()->format('Y-m-d').'.ags', $online['filename']);

        $offline = $service->export(CustomerPortableArchiveService::SCOPE_OFFLINE);
        $this->assertSame(1, $offline['count']);
        $this->assertSame('customers-offline-'.now()->format('Y-m-d').'.ags', $offline['filename']);

        @unlink($all['path']);
        @unlink($online['path']);
        @unlink($offline['path']);
    }

    public function test_export_import_round_trip_preserves_channel(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('customers/photos/a.webp', 'photo-bytes');

        $this->makeOfflineCustomer('CUS-20001', 'Retail Buyer', '03002220001', 'customers/photos/a.webp');
        $this->makeOnlineCustomer('CUS-20002', 'Web Buyer', 'web@example.com', '03002220002');

        $service = app(CustomerPortableArchiveService::class);
        $export = $service->export(CustomerPortableArchiveService::SCOPE_ALL);

        Customer::query()->delete();
        $this->assertSame(0, Customer::query()->count());

        $result = $service->importFromPath($export['path']);
        $this->assertSame(2, $result['created']);

        $offline = Customer::query()->where('customer_number', 'CUS-20001')->first();
        $online = Customer::query()->where('customer_number', 'CUS-20002')->first();

        $this->assertNotNull($offline);
        $this->assertTrue($offline->isOffline());
        $this->assertSame('Retail Buyer', $offline->display_name);
        $this->assertTrue(Storage::disk('public')->exists((string) $offline->profile_photo_path));

        $this->assertNotNull($online);
        $this->assertTrue($online->isOnline());
        $this->assertSame('web@example.com', $online->email);

        @unlink($export['path']);
    }

    private function makeOfflineCustomer(string $number, string $name, string $phone, ?string $photo = null): Customer
    {
        return app(CustomerService::class)->createOffline([
            'customer_number' => $number,
            'display_name' => $name,
            'name_en' => $name,
            'city_en' => 'Lahore',
            'city_key' => 'lahore',
            'address_quick' => 'Test street',
            'opening_balance' => 0,
            'phoneRows' => [['mobile_number' => $phone]],
            'is_active' => true,
            'profile_photo_path' => $photo,
        ]);
    }

    private function makeOnlineCustomer(string $number, string $name, string $email, string $phone): Customer
    {
        $parts = explode(' ', $name, 2);

        return Customer::query()->create([
            'customer_number' => $number,
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => $parts[0] ?? $name,
            'last_name' => $parts[1] ?? '-',
            'display_name' => $name,
            'name_en' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make('password-secret'),
            'address_line1' => 'Online street',
            'city' => 'Lahore',
            'city_en' => 'Lahore',
            'city_key' => 'lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'opening_balance' => 0,
            'contacts' => [['mobile_number' => $phone]],
            'is_active' => true,
            'accepts_marketing' => false,
        ]);
    }
}
