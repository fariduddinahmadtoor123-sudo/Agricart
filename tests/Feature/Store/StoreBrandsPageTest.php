<?php

namespace Tests\Feature\Store;

use App\Models\Brand;
use App\Models\BrandCustomerReview;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Services\Store\StoreBrandService;
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
            ->assertSee(__('agricart.storefront.brands.search_placeholder'), false)
            ->assertSee('Trusted Brand', false)
            ->assertSee(route('store.brand', $brand->slug), false)
            ->assertDontSee('Hidden Brand', false);
    }

    public function test_brands_index_search_filters_by_description_and_product_name(): void
    {
        $pipeBrand = Brand::query()->create([
            'code' => 'BR-PIPE',
            'slug' => 'pipe-brand',
            'status' => 'active',
            'name_en' => 'AquaFlow',
            'name_ur' => 'ایکوا فلو',
            'image_path' => 'brands/pipe.webp',
            'description_en' => 'Specialist pipe and irrigation fittings.',
        ]);

        $tireBrand = Brand::query()->create([
            'code' => 'BR-TIRE',
            'slug' => 'tire-brand',
            'status' => 'active',
            'name_en' => 'RoadGrip',
            'name_ur' => 'روڈ گرپ',
            'image_path' => 'brands/tire.webp',
            'description_en' => 'Agricultural and light truck tires.',
        ]);

        Brand::query()->create([
            'code' => 'BR-OTHER',
            'slug' => 'other-brand',
            'status' => 'active',
            'name_en' => 'Generic Tools',
            'name_ur' => 'عام اوزار',
            'image_path' => 'brands/other.webp',
            'description_en' => 'Hand tools and workshop supplies.',
        ]);

        $category = Category::query()->create([
            'code' => 'CAT-BRAND-SEARCH',
            'slug' => 'brand-search-cat',
            'status' => 'active',
            'name_en' => 'Brand Search Category',
            'name_ur' => 'برانڈ سرچ زمرہ',
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'brand_id' => $tireBrand->id,
            'slug' => 'tractor-tire-set',
            'code' => 'PRD-TIRE-1',
            'status' => 'active',
            'name_en' => 'Tractor Tire Set',
            'name_ur' => 'ٹریکٹر ٹائر سیٹ',
        ]);

        $this->get(route('store.brands', ['q' => 'pipe']))
            ->assertOk()
            ->assertSee('AquaFlow', false)
            ->assertDontSee('RoadGrip', false)
            ->assertDontSee('Generic Tools', false)
            ->assertSee(__('agricart.storefront.brands.search_clear'), false);

        $this->get(route('store.brands', ['q' => 'tire']))
            ->assertOk()
            ->assertSee('RoadGrip', false)
            ->assertDontSee('AquaFlow', false)
            ->assertDontSee('Generic Tools', false);

        $this->get(route('store.brands', ['q' => 'wrench-plus-missing']))
            ->assertOk()
            ->assertSee(__('agricart.storefront.brands.search_no_results', ['query' => 'wrench-plus-missing']), false)
            ->assertDontSee('AquaFlow', false)
            ->assertDontSee('RoadGrip', false);

        $this->assertNotNull($pipeBrand->id);
    }

    public function test_brands_search_finds_full_name_even_with_filler_words(): void
    {
        Brand::query()->create([
            'code' => 'BR-SKF',
            'slug' => 'skf',
            'status' => 'active',
            'name_en' => 'SKF',
            'name_ur' => 'ایس کے ایف',
            'image_path' => 'brands/skf.webp',
            'description_en' => 'Premium bearing brand.',
        ]);

        Brand::query()->create([
            'code' => 'BR-NOISE',
            'slug' => 'noise-brand',
            'status' => 'active',
            'name_en' => 'Farm Tools Co',
            'name_ur' => 'فارم ٹولز',
            'image_path' => 'brands/noise.webp',
            'description_en' => 'Every brand company in Pakistan needs hand tools.',
        ]);

        $this->get(route('store.brands', ['q' => 'SKF Brand Company Pakistan']))
            ->assertOk()
            ->assertSee('SKF', false)
            ->assertDontSee('Farm Tools Co', false);

        $this->get(route('store.brands', ['q' => 'SKF']))
            ->assertOk()
            ->assertSee('SKF', false)
            ->assertDontSee('Farm Tools Co', false);
    }

    public function test_brands_search_bearing_uses_product_titles_not_seo_noise(): void
    {
        $bearingBrand = Brand::query()->create([
            'code' => 'BR-BEAR',
            'slug' => 'bearing-house',
            'status' => 'active',
            'name_en' => 'Bearing House',
            'name_ur' => 'بیرنگ ہاؤس',
            'image_path' => 'brands/bearing.webp',
        ]);

        $noiseBrand = Brand::query()->create([
            'code' => 'BR-SEO',
            'slug' => 'seo-noise',
            'status' => 'active',
            'name_en' => 'Green Pumps',
            'name_ur' => 'گرین پمپس',
            'image_path' => 'brands/pumps.webp',
            'description_en' => 'Irrigation pumps and motors.',
        ]);

        $category = Category::query()->create([
            'code' => 'CAT-BEARING',
            'slug' => 'bearing-cat',
            'status' => 'active',
            'name_en' => 'Bearings',
            'name_ur' => 'بیرنگز',
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'brand_id' => $bearingBrand->id,
            'slug' => 'deep-groove-bearing',
            'code' => 'PRD-BEAR-1',
            'status' => 'active',
            'name_en' => 'Deep Groove Ball Bearing',
            'name_ur' => 'ڈیپ گروو بال بیرنگ',
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'brand_id' => $noiseBrand->id,
            'slug' => 'pump-motor',
            'code' => 'PRD-PUMP-1',
            'status' => 'active',
            'name_en' => 'Submersible Pump',
            'name_ur' => 'سبمرسیبل پمپ',
            'full_description_en' => 'Also useful near bearing housings on farms.',
            'seo_keywords_en' => 'bearing,farm,pump',
        ]);

        $this->get(route('store.brands', ['q' => 'bearing']))
            ->assertOk()
            ->assertSee('Bearing House', false)
            ->assertDontSee('Green Pumps', false);
    }

    public function test_brands_index_paginates_when_more_than_page_limit(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            Brand::query()->create([
                'code' => 'BR-PAGE-'.$i,
                'slug' => 'page-brand-'.$i,
                'status' => 'active',
                'name_en' => 'Page Brand '.$i,
                'name_ur' => 'صفحہ برانڈ '.$i,
                'image_path' => 'brands/page-'.$i.'.webp',
            ]);
        }

        $page = app(StoreBrandService::class)->listedBrands(null, 2);

        $this->assertSame(3, $page->total());
        $this->assertSame(2, $page->perPage());
        $this->assertTrue($page->hasPages());
        $this->assertCount(2, $page->items());
        $this->assertSame(StoreBrandService::LIST_PER_PAGE, 500);
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
