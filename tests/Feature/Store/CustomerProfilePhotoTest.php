<?php

namespace Tests\Feature\Store;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_online_customer_can_upload_profile_photo(): void
    {
        Storage::fake('public');

        $customer = $this->createCustomer('photo@test.test');

        $this->actingAs($customer, 'customer')
            ->post(route('store.customer.account.profile'), [
                'first_name' => 'Photo',
                'last_name' => 'Buyer',
                'email' => 'photo@test.test',
                'phone' => $customer->phone,
                'profile_photo' => UploadedFile::fake()->image('avatar.jpg', 240, 240),
            ])
            ->assertRedirect(route('store.customer.account'));

        $customer->refresh();

        $this->assertNotNull($customer->profile_photo_path);
        $this->assertStringStartsWith('customers/photos/', $customer->profile_photo_path);
        Storage::disk('public')->assertExists($customer->profile_photo_path);

        $this->actingAs($customer, 'customer')
            ->get(route('store.home'))
            ->assertOk()
            ->assertSee('ag-store-header__user-avatar--photo', false)
            ->assertSee($customer->profilePhotoUrl(), false);
    }

    public function test_profile_photo_rejects_oversized_files(): void
    {
        Storage::fake('public');

        $customer = $this->createCustomer('huge-photo@test.test');

        $this->actingAs($customer, 'customer')
            ->from(route('store.customer.account'))
            ->post(route('store.customer.account.profile'), [
                'first_name' => 'Photo',
                'last_name' => 'Buyer',
                'email' => 'huge-photo@test.test',
                'phone' => $customer->phone,
                'profile_photo' => UploadedFile::fake()->image('huge.jpg', 800, 800)->size(3072),
            ])
            ->assertRedirect(route('store.customer.account'))
            ->assertSessionHasErrors('profile_photo');

        $this->assertNull($customer->fresh()->profile_photo_path);
    }

    public function test_online_customer_can_remove_profile_photo(): void
    {
        Storage::fake('public');

        $customer = $this->createCustomer('remove-photo@test.test');
        $path = UploadedFile::fake()->image('keep.jpg', 120, 120)->store('customers/photos', 'public');
        $customer->update(['profile_photo_path' => $path]);

        $this->actingAs($customer, 'customer')
            ->post(route('store.customer.account.profile'), [
                'first_name' => 'Photo',
                'last_name' => 'Buyer',
                'email' => 'remove-photo@test.test',
                'phone' => $customer->phone,
                'remove_profile_photo' => '1',
            ])
            ->assertRedirect(route('store.customer.account'));

        $customer->refresh();
        $this->assertNull($customer->profile_photo_path);
        Storage::disk('public')->assertMissing($path);
    }

    private function createCustomer(string $email): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'CUS-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Photo',
            'last_name' => 'Buyer',
            'display_name' => 'Photo Buyer',
            'email' => $email,
            'phone' => '0300'.random_int(1000000, 9999999),
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);
    }
}
