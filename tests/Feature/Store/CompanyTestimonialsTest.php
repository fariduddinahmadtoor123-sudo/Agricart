<?php

namespace Tests\Feature\Store;

use App\Livewire\Store\ListCompanyTestimonials;
use App\Models\CompanyTestimonial;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyTestimonialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_reviews_page_lists_only_approved_testimonials(): void
    {
        CompanyTestimonial::query()->create([
            'display_name' => 'Ali Farmer',
            'rating' => 5,
            'body' => 'Agricart delivery and support were excellent for my farm shop.',
            'status' => CompanyTestimonial::STATUS_APPROVED,
            'source' => CompanyTestimonial::SOURCE_ADMIN,
            'is_featured' => true,
        ]);

        CompanyTestimonial::query()->create([
            'display_name' => 'Hidden Voice',
            'rating' => 2,
            'body' => 'This pending review must not appear on the public page yet.',
            'status' => CompanyTestimonial::STATUS_PENDING,
            'source' => CompanyTestimonial::SOURCE_ADMIN,
        ]);

        $this->get(route('store.testimonials'))
            ->assertOk()
            ->assertSee('Agricart delivery and support were excellent for my farm shop.', false)
            ->assertDontSee('This pending review must not appear on the public page yet.', false)
            ->assertSee(__('agricart.storefront.testimonials.heading'), false);
    }

    public function test_customer_can_submit_testimonial_and_it_stays_pending(): void
    {
        $customer = $this->createCustomer('voice@test.test');

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.testimonials.create'))
            ->assertOk()
            ->assertSee(__('agricart.storefront.testimonials.write_heading'), false);

        $this->actingAs($customer, 'customer')
            ->post(route('store.customer.testimonials.store'), [
                'rating' => 5,
                'title' => 'Great service',
                'body' => 'Fair prices and helpful staff every time I ordered from Agricart.',
            ])
            ->assertRedirect(route('store.testimonials'))
            ->assertSessionHas('customer_auth_status', __('agricart.storefront.testimonials.submitted'));

        $this->assertDatabaseHas('company_testimonials', [
            'customer_id' => $customer->id,
            'rating' => 5,
            'title' => 'Great service',
            'status' => CompanyTestimonial::STATUS_PENDING,
            'source' => CompanyTestimonial::SOURCE_CUSTOMER,
        ]);

        $this->get(route('store.testimonials'))
            ->assertOk()
            ->assertDontSee('Fair prices and helpful staff every time I ordered from Agricart.', false);
    }

    public function test_customer_can_update_approved_review_and_it_stays_published(): void
    {
        $customer = $this->createCustomer('edit@test.test');

        CompanyTestimonial::query()->create([
            'customer_id' => $customer->id,
            'display_name' => 'Test Customer',
            'rating' => 4,
            'body' => 'Original Agricart review text before the customer edit.',
            'status' => CompanyTestimonial::STATUS_APPROVED,
            'is_featured' => true,
            'source' => CompanyTestimonial::SOURCE_CUSTOMER,
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('store.testimonials'))
            ->assertOk()
            ->assertSee(__('agricart.storefront.testimonials.edit_cta'), false);

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.testimonials.create'))
            ->assertOk()
            ->assertSee(__('agricart.storefront.testimonials.edit_heading'), false)
            ->assertSee('Original Agricart review text before the customer edit.', false);

        $this->actingAs($customer, 'customer')
            ->post(route('store.customer.testimonials.store'), [
                'rating' => 5,
                'title' => 'Updated title',
                'body' => 'Updated Agricart review text after the customer edit change.',
            ])
            ->assertRedirect(route('store.testimonials'))
            ->assertSessionHas('customer_auth_status', __('agricart.storefront.testimonials.updated'));

        $this->assertDatabaseHas('company_testimonials', [
            'customer_id' => $customer->id,
            'rating' => 5,
            'title' => 'Updated title',
            'body' => 'Updated Agricart review text after the customer edit change.',
            'status' => CompanyTestimonial::STATUS_APPROVED,
            'is_featured' => true,
        ]);

        $this->get(route('store.testimonials'))
            ->assertOk()
            ->assertSee('Updated Agricart review text after the customer edit change.', false);
    }

    public function test_admin_can_approve_and_feature_testimonial(): void
    {
        $customer = $this->createCustomer('approve@test.test');
        $item = CompanyTestimonial::query()->create([
            'customer_id' => $customer->id,
            'display_name' => $customer->displayName(),
            'rating' => 4,
            'body' => 'Reliable online ordering for tractor parts in our village.',
            'status' => CompanyTestimonial::STATUS_PENDING,
            'source' => CompanyTestimonial::SOURCE_CUSTOMER,
        ]);

        Livewire::actingAs($this->superAdmin())
            ->test(ListCompanyTestimonials::class)
            ->call('approve', $item->id)
            ->call('toggleFeatured', $item->id);

        $item->refresh();
        $this->assertSame(CompanyTestimonial::STATUS_APPROVED, $item->status);
        $this->assertTrue($item->is_featured);

        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee('Reliable online ordering for tractor parts in our village.', false);
    }

    public function test_footer_includes_reviews_link(): void
    {
        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee(route('store.testimonials'), false)
            ->assertSee(__('agricart.storefront.testimonials.nav'), false);
    }

    public function test_write_form_shows_interactive_stars_without_default_five(): void
    {
        $customer = $this->createCustomer('stars@test.test');

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.testimonials.create'))
            ->assertOk()
            ->assertSee('data-store-star-picker', false)
            ->assertSee(__('agricart.storefront.testimonials.rating_none'), false)
            ->assertDontSee('value="5" checked', false);
    }

    public function test_approved_testimonials_appear_in_footer_carousel_not_home_strip(): void
    {
        CompanyTestimonial::query()->create([
            'display_name' => 'Carousel Farmer',
            'rating' => 5,
            'body' => 'Carousel proof review text for Agricart service quality.',
            'status' => CompanyTestimonial::STATUS_APPROVED,
            'source' => CompanyTestimonial::SOURCE_ADMIN,
        ]);

        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee('data-store-testimonials-carousel', false)
            ->assertSee(__('agricart.storefront.testimonials.carousel_title'), false)
            ->assertDontSee('Customer voices', false)
            ->assertSee('Carousel proof review text for Agricart service quality.', false)
            ->assertSee('data-store-testimonial-dialog', false)
            ->assertSee('data-store-testimonial-open', false)
            ->assertDontSee(__('agricart.storefront.testimonials.home_heading'), false)
            ->assertDontSee('ag-store-testimonials-strip', false);
    }

    public function test_long_carousel_review_offers_read_more(): void
    {
        $longBody = str_repeat('Agricart delivery and support have been excellent for our farm. ', 8);

        CompanyTestimonial::query()->create([
            'display_name' => 'Long Review Farmer',
            'title' => 'Reliable partner',
            'rating' => 5,
            'body' => $longBody,
            'status' => CompanyTestimonial::STATUS_APPROVED,
            'source' => CompanyTestimonial::SOURCE_ADMIN,
        ]);

        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee(__('agricart.storefront.testimonials.read_more'), false)
            ->assertSee('Reliable partner', false)
            ->assertSee($longBody, false);
    }

    private function createCustomer(string $email): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'CUS-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'display_name' => 'Test Customer',
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
