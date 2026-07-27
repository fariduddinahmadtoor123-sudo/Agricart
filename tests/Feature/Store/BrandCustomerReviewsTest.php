<?php

namespace Tests\Feature\Store;

use App\Livewire\Store\ListBrandCustomerReviews;
use App\Models\Brand;
use App\Models\BrandCustomerReview;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\Store\BrandCustomerReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class BrandCustomerReviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_brand_review_and_admin_can_publish_it(): void
    {
        $brand = Brand::query()->create([
            'code' => 'BR-REV',
            'slug' => 'review-brand',
            'status' => 'active',
            'name_en' => 'Review Brand',
            'name_ur' => 'ریویو برانڈ',
            'image_path' => 'brands/review.webp',
        ]);

        $customer = $this->createCustomer('brand-reviewer@test.test');

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.brand-reviews.create', $brand->slug))
            ->assertOk()
            ->assertSee(__('agricart.storefront.brand_reviews.write_heading', ['brand' => $brand->name_en]), false);

        $this->actingAs($customer, 'customer')
            ->post(route('store.customer.brand-reviews.store', $brand->slug), [
                'rating' => 5,
                'body' => 'This brand spare parts quality is excellent for our workshop.',
            ])
            ->assertRedirect(route('store.brand', $brand->slug))
            ->assertSessionHas('customer_auth_status', __('agricart.storefront.brand_reviews.submitted'));

        $review = BrandCustomerReview::query()->first();
        $this->assertNotNull($review);
        $this->assertSame(BrandCustomerReview::STATUS_PENDING, $review->status);

        $this->get(route('store.brand', $brand->slug))
            ->assertOk()
            ->assertDontSee('This brand spare parts quality is excellent', false);

        $admin = $this->superAdmin();
        app(BrandCustomerReviewService::class)
            ->setStatus($review, BrandCustomerReview::STATUS_APPROVED, $admin);

        $this->get(route('store.brand', $brand->slug))
            ->assertOk()
            ->assertSee('This brand spare parts quality is excellent', false)
            ->assertSee(__('agricart.storefront.brand_reviews.eyebrow'), false);
    }

    public function test_admin_livewire_can_approve_pending_brand_review(): void
    {
        $brand = Brand::query()->create([
            'code' => 'BR-ADM',
            'slug' => 'admin-brand',
            'status' => 'active',
            'name_en' => 'Admin Brand',
            'name_ur' => 'ایڈمن برانڈ',
            'image_path' => 'brands/admin.webp',
        ]);

        $customer = $this->createCustomer('mod-brand@test.test');

        $review = BrandCustomerReview::query()->create([
            'brand_id' => $brand->id,
            'customer_id' => $customer->id,
            'rating' => 4,
            'body' => 'Solid brand with good after sales support in Punjab.',
            'status' => BrandCustomerReview::STATUS_PENDING,
        ]);

        Livewire::actingAs($this->superAdmin())
            ->test(ListBrandCustomerReviews::class)
            ->call('approve', $review->id)
            ->assertSuccessful();

        $this->assertSame(BrandCustomerReview::STATUS_APPROVED, $review->fresh()->status);
    }

    private function createCustomer(string $email): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'CUS-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Brand',
            'last_name' => 'Reviewer',
            'display_name' => 'Brand Reviewer',
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

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_system' => true],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
