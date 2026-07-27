<?php

namespace Tests\Feature\Store;

use App\Models\Brand;
use App\Models\BrandCustomerReview;
use App\Models\Customer;
use App\Support\Catalog\BrandAgricartReviewCriteria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StoreBrandsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_brands_index_and_carousel_show_active_brands_with_names(): void
    {
        $brand = Brand::query()->create([
            'code' => 'BR-TRUST',
            'slug' => 'trusted-brand',
            'status' => 'active',
            'name_en' => 'Trusted Brand',
            'name_ur' => 'معتبر برانڈ',
            'image_path' => 'brands/trusted.webp',
            'description_en' => 'A reliable farm brand for Pakistan.',
            'agricart_rating' => 8,
            'agricart_rating_criteria' => BrandAgricartReviewCriteria::forStorage($this->completeCriteria()),
            'agricart_rating_reason_en' => 'Strong market presence and spare parts support.',
        ]);

        Brand::query()->create([
            'code' => 'BR-HIDE',
            'slug' => 'hidden-brand',
            'status' => 'inactive',
            'name_en' => 'Hidden Brand',
            'name_ur' => 'چھپا برانڈ',
            'image_path' => 'brands/hidden.webp',
        ]);

        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee('data-store-brands-carousel', false)
            ->assertSee(__('agricart.storefront.brands.carousel_title'), false)
            ->assertSee('Trusted Brand', false)
            ->assertSee('معتبر برانڈ', false)
            ->assertDontSee('Hidden Brand', false);

        $this->get(route('store.brands'))
            ->assertOk()
            ->assertSee(__('agricart.storefront.brands.heading'), false)
            ->assertSee('Trusted Brand', false)
            ->assertSee(route('store.brand', $brand->slug), false)
            ->assertDontSee('Hidden Brand', false);
    }

    public function test_brand_detail_shows_agricart_review_like_product_page(): void
    {
        $brand = Brand::query()->create([
            'code' => 'BR-DETAIL',
            'slug' => 'detail-brand',
            'status' => 'active',
            'name_en' => 'Detail Brand',
            'name_ur' => 'تفصیلی برانڈ',
            'image_path' => 'brands/detail.webp',
            'description_en' => 'Full editorial brand profile for farmers.',
            'website_url' => 'https://example.com/brand',
            'agricart_rating' => 9,
            'agricart_rating_criteria' => BrandAgricartReviewCriteria::forStorage($this->completeCriteria()),
            'agricart_rating_reason_en' => 'Excellent durability and after-sales service.',
        ]);

        $this->get(route('store.brand', $brand->slug))
            ->assertOk()
            ->assertSee('Detail Brand', false)
            ->assertSee('تفصیلی برانڈ', false)
            ->assertSee('Full editorial brand profile for farmers.', false)
            ->assertSee(__('agricart.storefront.product.brand_review.eyebrow'), false)
            ->assertSee('Excellent durability and after-sales service.', false)
            ->assertSee(__('agricart.brands.review_criteria.product_quality.label'), false)
            ->assertSee(__('agricart.storefront.brand_reviews.rating_agricart_label'), false);
    }

    public function test_brand_surfaces_show_dual_agricart_and_customer_ratings(): void
    {
        $brand = Brand::query()->create([
            'code' => 'BR-DUAL',
            'slug' => 'dual-rating-brand',
            'status' => 'active',
            'name_en' => 'Dual Rating Brand',
            'name_ur' => 'دو ریٹنگ برانڈ',
            'image_path' => 'brands/dual.webp',
            'agricart_rating' => 8,
            'agricart_rating_criteria' => BrandAgricartReviewCriteria::forStorage($this->completeCriteria()),
        ]);

        $customer = Customer::query()->create([
            'customer_number' => 'CUS-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Dual',
            'last_name' => 'Rater',
            'display_name' => 'Dual Rater',
            'email' => 'dual-rater@test.test',
            'phone' => '0300'.random_int(1000000, 9999999),
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        BrandCustomerReview::query()->create([
            'brand_id' => $brand->id,
            'customer_id' => $customer->id,
            'rating' => 4,
            'body' => 'Good brand overall with reliable spare parts availability.',
            'status' => BrandCustomerReview::STATUS_APPROVED,
        ]);

        $this->get(route('store.brand', $brand->slug))
            ->assertOk()
            ->assertSee(__('agricart.storefront.brand_reviews.rating_agricart_label'), false)
            ->assertSee(__('agricart.storefront.brand_reviews.rating_customers_label'), false)
            ->assertSee('4.0 / 5', false)
            ->assertSee('"ratingValue":4', false)
            ->assertSee('"bestRating":5', false);

        $this->get(route('store.brands'))
            ->assertOk()
            ->assertSee(__('agricart.storefront.brand_reviews.rating_customers_label'), false)
            ->assertSee('4.0 / 5', false);

        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee(__('agricart.storefront.brand_reviews.rating_customers_label'), false)
            ->assertSee('4.0 / 5', false);
    }

    /**
     * @return array<string, array{rating: int, reason_en: string, reason_ur: string}>
     */
    private function completeCriteria(): array
    {
        $criteria = BrandAgricartReviewCriteria::normalizeDetailed(null);

        foreach (BrandAgricartReviewCriteria::keys() as $key) {
            $criteria[$key] = [
                'rating' => 8,
                'reason_en' => 'Strong '.$key,
                'reason_ur' => 'مضبوط '.$key,
            ];
        }

        return $criteria;
    }
}
