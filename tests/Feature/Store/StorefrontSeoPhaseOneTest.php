<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorefrontSeoPhaseOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_local_business_schema_includes_company_address(): void
    {
        CompanyProfile::current()->update([
            'business_name_en' => 'Agricart Farms',
            'email' => 'hello@agricart.test',
            'phones' => [['phone_number' => '+923001112233', 'is_primary' => true]],
            'addresses' => [['address_en' => 'Main Bazaar, Lahore', 'postal_code' => '54000', 'is_primary' => true]],
        ]);

        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee('"@type":"LocalBusiness"', false)
            ->assertSee('"@type":"PostalAddress"', false)
            ->assertSee('Main Bazaar, Lahore', false)
            ->assertSee('+923001112233', false);
    }

    public function test_category_faq_page_schema_is_emitted(): void
    {
        $category = Category::query()->create([
            'slug' => 'faq-seeds',
            'code' => 'CAT-FAQ-SEEDS',
            'status' => 'active',
            'name_en' => 'FAQ Seeds',
            'name_ur' => 'بیج',
            'faqs' => [[
                'question_en' => 'When to sow?',
                'question_ur' => 'کب بوئیں؟',
                'answer_en' => 'In spring.',
                'answer_ur' => 'بہار میں۔',
            ]],
        ]);

        $this->get(route('store.category', $category->slug))
            ->assertOk()
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('When to sow?', false)
            ->assertSee('In spring.', false);
    }

    public function test_product_aggregate_rating_schema_and_share_links(): void
    {
        $category = Category::query()->create([
            'slug' => 'rated-tools',
            'code' => 'CAT-RATED',
            'status' => 'active',
            'name_en' => 'Rated Tools',
            'name_ur' => 'اوزار',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'rated-spade',
            'code' => 'PRD-RATED',
            'status' => 'active',
            'name_en' => 'Rated Spade',
            'name_ur' => 'بیلچہ',
            'retail_price' => 250,
            'stock_qty' => 5,
        ]);

        $customer = Customer::query()->create([
            'customer_number' => 'CUS-SEO1',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Review',
            'last_name' => 'Er',
            'email' => 'reviewer@test.test',
            'phone' => '03001234567',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        ProductReview::query()->create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'submission_path' => ProductReview::PATH_PROOF,
            'rating' => 5,
            'body' => 'Works well',
            'status' => ProductReview::STATUS_APPROVED,
        ]);

        $this->get(route('store.product', $product->slug))
            ->assertOk()
            ->assertSee('"@type":"AggregateRating"', false)
            ->assertSee('"ratingValue":5', false)
            ->assertSee(__('agricart.storefront.product.share_whatsapp'), false)
            ->assertSee('wa.me/?text=', false);
    }

    public function test_verification_and_tracking_tags_render_only_when_configured(): void
    {
        $this->get(route('store.home'))
            ->assertOk()
            ->assertDontSee('google-site-verification', false)
            ->assertDontSee('googletagmanager.com/gtm.js', false);

        StoreSetting::current()->update([
            'google_site_verification' => 'google-token-abc',
            'bing_site_verification' => 'bing-token-xyz',
            'gtm_container_id' => 'GTM-TEST123',
            'meta_pixel_id' => '9988776655',
        ]);

        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee('name="google-site-verification" content="google-token-abc"', false)
            ->assertSee('name="msvalidate.01" content="bing-token-xyz"', false)
            ->assertSee('GTM-TEST123', false)
            ->assertSee('9988776655', false);
    }

    public function test_google_merchant_feed_lists_active_product_with_image_and_price(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/feed-spade.webp', 'image');

        $category = Category::query()->create([
            'slug' => 'feed-tools',
            'code' => 'CAT-FEED',
            'status' => 'active',
            'name_en' => 'Feed Tools',
            'name_ur' => 'اوزار',
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'feed-spade',
            'code' => 'PRD-FEED',
            'sku' => 'SKU-FEED-1',
            'status' => 'active',
            'name_en' => 'Feed Spade',
            'name_ur' => 'بیلچہ',
            'retail_price' => 199,
            'stock_qty' => 3,
            'image_path' => 'products/feed-spade.webp',
            'short_description_en' => 'A solid spade for farms.',
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'inactive-feed',
            'code' => 'PRD-FEED-OFF',
            'status' => 'inactive',
            'name_en' => 'Inactive Feed',
            'name_ur' => 'بند',
            'retail_price' => 99,
            'image_path' => 'products/feed-spade.webp',
        ]);

        $response = $this->get(route('store.google_merchant_feed'));

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<g:id>SKU-FEED-1</g:id>', false)
            ->assertSee('<g:title>Feed Spade</g:title>', false)
            ->assertSee('199.00 PKR', false)
            ->assertDontSee('Inactive Feed', false);
    }
}
