<?php

namespace Tests\Feature\Store;

use App\Livewire\Store\ListProductReviews;
use App\Models\Category;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductReviewPhoto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCustomerReviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_always_shows_customer_reviews_tab(): void
    {
        $product = $this->createProduct('review-tab-product');

        $this->get(route('store.product', $product->slug))
            ->assertOk()
            ->assertSee('data-store-pdp-tab="customer-reviews"', false)
            ->assertSee(__('agricart.storefront.product.tabs.customer_reviews'), false)
            ->assertSee(__('agricart.storefront.reviews.empty'), false);
    }

    public function test_customer_without_delivery_can_open_proof_form_but_cannot_submit_without_proof(): void
    {
        $customer = $this->createCustomer('no-delivery@test.test');
        $product = $this->createProduct('undelivered-product');

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.reviews.create', $product->slug))
            ->assertOk()
            ->assertSee(__('agricart.storefront.reviews.write_hint_proof'), false)
            ->assertSee(__('agricart.storefront.reviews.proofs_section_title'), false)
            ->assertSee(__('agricart.storefront.reviews.proofs_drop_cta'), false);

        $this->actingAs($customer, 'customer')
            ->post(route('store.customer.reviews.store', $product->slug), [
                'rating' => 5,
                'body' => 'This should not be accepted without proof files.',
            ])
            ->assertSessionHasErrors('proofs');

        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_customer_can_submit_proof_review_without_delivery_and_it_stays_pending(): void
    {
        Storage::fake('public');

        $customer = $this->createCustomer('proof-path@test.test');
        $product = $this->createProduct('proof-path-product');

        $this->actingAs($customer, 'customer')
            ->post(route('store.customer.reviews.store', $product->slug), [
                'rating' => 4,
                'body' => 'Used this product on my farm after buying locally with receipt.',
                'proofs' => [UploadedFile::fake()->image('receipt.jpg', 200, 200)],
                'proof_note' => 'Bought from local dealer in Lahore.',
            ])
            ->assertRedirect(route('store.product', $product->slug));

        $this->assertDatabaseHas('product_reviews', [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'rating' => 4,
            'submission_path' => ProductReview::PATH_PROOF,
            'status' => ProductReview::STATUS_PENDING,
            'proof_note' => 'Bought from local dealer in Lahore.',
        ]);

        $review = ProductReview::query()->where('product_id', $product->id)->first();
        $this->assertNotNull($review);
        $this->assertCount(1, $review->proofPhotos);
        $this->assertSame(ProductReviewPhoto::KIND_PROOF, $review->proofPhotos->first()->kind);

        $this->get(route('store.product', $product->slug))
            ->assertOk()
            ->assertDontSee('Used this product on my farm after buying locally with receipt.', false);
    }

    public function test_customer_can_submit_review_after_delivery_and_it_stays_pending(): void
    {
        $customer = $this->createCustomer('delivered@test.test');
        $product = $this->createProduct('delivered-product');
        $this->createDeliveredOrder($customer, $product);

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.reviews.create', $product->slug))
            ->assertOk()
            ->assertSee(__('agricart.storefront.reviews.write_heading'), false)
            ->assertSee(__('agricart.storefront.reviews.write_hint_verified'), false)
            ->assertDontSee(__('agricart.storefront.reviews.proofs_section_title'), false);

        $this->actingAs($customer, 'customer')
            ->post(route('store.customer.reviews.store', $product->slug), [
                'rating' => 4,
                'body' => 'Good quality after delivery. Would buy again from Agricart.',
            ])
            ->assertRedirect(route('store.product', $product->slug));

        $this->assertDatabaseHas('product_reviews', [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'rating' => 4,
            'submission_path' => ProductReview::PATH_VERIFIED_PURCHASE,
            'status' => ProductReview::STATUS_PENDING,
        ]);

        $this->get(route('store.product', $product->slug))
            ->assertOk()
            ->assertDontSee('Good quality after delivery. Would buy again from Agricart.', false);
    }

    public function test_approved_reviews_appear_on_product_page_with_path_badges(): void
    {
        $verifiedCustomer = $this->createCustomer('approved@test.test');
        $proofCustomer = $this->createCustomer('approved-proof@test.test');
        $product = $this->createProduct('approved-product');
        $order = $this->createDeliveredOrder($verifiedCustomer, $product);

        ProductReview::query()->create([
            'product_id' => $product->id,
            'customer_id' => $verifiedCustomer->id,
            'online_sale_order_id' => $order->id,
            'online_sale_order_item_id' => $order->items->first()->id,
            'submission_path' => ProductReview::PATH_VERIFIED_PURCHASE,
            'rating' => 5,
            'body' => 'Excellent verified purchase experience.',
            'status' => ProductReview::STATUS_APPROVED,
        ]);

        ProductReview::query()->create([
            'product_id' => $product->id,
            'customer_id' => $proofCustomer->id,
            'submission_path' => ProductReview::PATH_PROOF,
            'rating' => 4,
            'body' => 'Solid product after reviewing with proof files.',
            'status' => ProductReview::STATUS_APPROVED,
        ]);

        $this->get(route('store.product', $product->slug))
            ->assertOk()
            ->assertSee('Excellent verified purchase experience.', false)
            ->assertSee('Solid product after reviewing with proof files.', false)
            ->assertSee(__('agricart.storefront.reviews.verified'), false)
            ->assertSee(__('agricart.storefront.reviews.reviewed_with_proof'), false);
    }

    public function test_admin_can_approve_pending_review_and_sees_proof_path(): void
    {
        Storage::fake('public');

        $customer = $this->createCustomer('moderation@test.test');
        $product = $this->createProduct('moderation-product');
        $admin = $this->superAdmin();

        $review = ProductReview::query()->create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'submission_path' => ProductReview::PATH_PROOF,
            'rating' => 3,
            'body' => 'Average product, waiting for staff approval.',
            'proof_note' => 'Receipt from local shop.',
            'status' => ProductReview::STATUS_PENDING,
        ]);

        ProductReviewPhoto::query()->create([
            'product_review_id' => $review->id,
            'path' => UploadedFile::fake()->image('proof.jpg')->store('product-reviews', 'public'),
            'kind' => ProductReviewPhoto::KIND_PROOF,
            'sort_order' => 0,
        ]);

        Livewire::actingAs($admin)
            ->test(ListProductReviews::class)
            ->assertSee('Average product, waiting for staff approval.')
            ->assertSee(__('agricart.store.admin.reviews.path_proof'))
            ->assertSee(__('agricart.store.admin.reviews.policy_checklist'))
            ->assertSee(__('agricart.store.admin.reviews.view'))
            ->call('openView', $review->id)
            ->assertSee('Receipt from local shop.')
            ->assertSee(__('agricart.store.admin.reviews.detail_label'))
            ->call('approve', $review->id);

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'status' => ProductReview::STATUS_APPROVED,
            'moderated_by' => $admin->id,
        ]);
    }

    public function test_delivered_order_line_shows_write_review_link(): void
    {
        $customer = $this->createCustomer('order-cta@test.test');
        $product = $this->createProduct('order-cta-product');
        $order = $this->createDeliveredOrder($customer, $product);

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.orders.show', $order->order_number))
            ->assertOk()
            ->assertSee(route('store.customer.reviews.create', $product->slug), false)
            ->assertSee(__('agricart.storefront.reviews.write_short'), false);
    }

    public function test_order_line_shows_review_status_when_submitted(): void
    {
        $customer = $this->createCustomer('order-status@test.test');
        $product = $this->createProduct('order-status-product');
        $order = $this->createDeliveredOrder($customer, $product);

        ProductReview::query()->create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'online_sale_order_id' => $order->id,
            'submission_path' => ProductReview::PATH_VERIFIED_PURCHASE,
            'rating' => 5,
            'body' => 'Submitted and waiting for moderation status on order page.',
            'status' => ProductReview::STATUS_PENDING,
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.orders.show', $order->order_number))
            ->assertOk()
            ->assertSee(__('agricart.storefront.reviews.status_pending'), false);
    }

    public function test_customer_can_submit_review_with_photo_pending_until_approve(): void
    {
        Storage::fake('public');

        $customer = $this->createCustomer('photo-review@test.test');
        $product = $this->createProduct('photo-review-product');
        $this->createDeliveredOrder($customer, $product);

        $this->actingAs($customer, 'customer')
            ->post(route('store.customer.reviews.store', $product->slug), [
                'rating' => 5,
                'body' => 'Great product with a clear photo from my farm use.',
                'photos' => [UploadedFile::fake()->image('review.jpg', 200, 200)],
            ])
            ->assertRedirect(route('store.product', $product->slug));

        $review = ProductReview::query()->where('product_id', $product->id)->first();
        $this->assertNotNull($review);
        $this->assertSame(ProductReview::STATUS_PENDING, $review->status);
        $this->assertSame(ProductReview::PATH_VERIFIED_PURCHASE, $review->submission_path);
        $this->assertCount(1, $review->galleryPhotos);
        $this->assertSame(ProductReviewPhoto::KIND_GALLERY, $review->galleryPhotos->first()->kind);

        $this->get(route('store.product', $product->slug))
            ->assertOk()
            ->assertDontSee('Great product with a clear photo from my farm use.', false);

        $admin = $this->superAdmin();
        Livewire::actingAs($admin, 'web')
            ->test(ListProductReviews::class)
            ->call('approve', $review->id)
            ->call('openView', $review->id)
            ->set('replyText', 'Thanks for your verified feedback.')
            ->call('saveReply');

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'status' => ProductReview::STATUS_APPROVED,
            'admin_reply' => 'Thanks for your verified feedback.',
        ]);

        auth('customer')->logout();
        auth('web')->logout();
        auth()->shouldUse('web');

        $this->get(route('store.product', $product->slug))
            ->assertOk()
            ->assertSee('Great product with a clear photo from my farm use.', false)
            ->assertSee('Thanks for your verified feedback.', false)
            ->assertSee(__('agricart.storefront.reviews.admin_reply'), false);
    }

    private function createProduct(string $slug): Product
    {
        $category = Category::query()->create([
            'slug' => 'cat-'.$slug,
            'code' => 'CAT-'.strtoupper(substr(md5($slug), 0, 6)),
            'status' => 'active',
            'name_en' => 'Category '.$slug,
            'name_ur' => 'زمرہ '.$slug,
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-'.strtoupper(substr(md5($slug), 0, 6)),
            'sku' => 'SKU-'.$slug,
            'name_en' => 'Product '.$slug,
            'name_ur' => 'پروڈکٹ '.$slug,
            'slug' => $slug,
            'full_description_en' => 'Description for '.$slug,
        ]);
    }

    private function createCustomer(string $email): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'CUS-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Review',
            'last_name' => 'Buyer',
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

    private function createDeliveredOrder(Customer $customer, Product $product): OnlineSaleOrder
    {
        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'ONL-REV-'.strtoupper(substr(md5($product->slug.$customer->id), 0, 8)),
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'locale' => 'en',
            'order_status' => OnlineSaleOrder::STATUS_CONVERTED,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_DELIVERED,
            'payment_status' => 'paid',
            'subtotal' => 100,
            'total_amount' => 100,
            'amount_paid' => 100,
            'ship_contact_name' => $customer->displayName(),
            'ship_phone' => $customer->phone,
            'converted_at' => now(),
        ]);

        OnlineSaleOrderItem::query()->create([
            'online_sale_order_id' => $order->id,
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => $product->name_en,
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        return $order->fresh('items.product');
    }

    private function superAdmin(): User
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Reviews Admin',
            'email' => 'reviews-admin@test.test',
            'phone_numbers' => [[
                'contact_person' => 'Admin',
                'contact_person_ur' => 'Admin',
                'phone_number' => '03005556677',
            ]],
        ]);
    }
}
