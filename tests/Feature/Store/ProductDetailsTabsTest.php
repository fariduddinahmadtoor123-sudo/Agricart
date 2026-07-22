<?php

namespace Tests\Feature\Store;

use App\Enums\ShippingChargeMode;
use App\Models\CatalogControl;
use App\Models\Category;
use App\Models\DeliveryMethod;
use App\Models\Product;
use App\Models\SalesChannel;
use App\Services\Logistics\DeliveryMethodService;
use App\Services\Sales\SaleService;
use App\Services\SalesChannels\SalesChannelService;
use App\Services\Store\StorefrontProductPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailsTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_content_sections_render_as_individual_accessible_tabs(): void
    {
        $category = Category::query()->create([
            'slug' => 'seeds',
            'code' => 'CAT-SEEDS',
            'status' => 'active',
            'name_en' => 'Seeds',
            'name_ur' => 'بیج',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-000001',
            'sku' => 'WHEAT-1',
            'name_en' => 'Wheat Seed Pack',
            'name_ur' => 'گندم بیج پیک',
            'slug' => 'wheat-seed-pack',
            'full_description_en' => 'Detailed product information.',
            'usage_guidelines_en' => 'Use according to the crop plan.',
            'warnings_en' => 'Keep away from children.',
            'customs_notes_en' => 'Verify the destination tariff.',
            'hs_code' => '1209.99',
        ]);

        $factKeys = collect(app(StorefrontProductPresenter::class)->identityFacts($product))->pluck('key');
        $this->assertFalse($factKeys->contains('hs'));

        $response = $this->get(route('store.product', $product->slug));

        $response
            ->assertOk()
            ->assertSeeInOrder(['Description', 'Usage guidelines', 'Warnings', 'Customs Notes', 'HS Code'])
            ->assertDontSee(__('agricart.storefront.product.facts.hs_code'), false)
            ->assertSee('data-store-pdp-tab="content-0"', false)
            ->assertSee('aria-controls="store-pdp-panel-content-0"', false)
            ->assertSee('data-store-pdp-panel="content-1"', false)
            ->assertSee('aria-labelledby="store-pdp-tab-content-1"', false);
    }

    public function test_product_page_shows_delivery_tab_for_assigned_methods(): void
    {
        $category = Category::query()->create([
            'slug' => 'fertilizer',
            'code' => 'CAT-FERT',
            'status' => 'active',
            'name_en' => 'Fertilizer',
            'name_ur' => 'کھاد',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-DEL-1',
            'sku' => 'UREA-1',
            'name_en' => 'Urea Bag',
            'name_ur' => 'یوریا بیگ',
            'slug' => 'urea-bag',
            'full_description_en' => 'High quality urea.',
            'retail_price' => 100,
            'stock_qty' => 50,
        ]);

        $method = app(DeliveryMethodService::class)->create([
            'name_en' => 'Store Courier',
            'name_ur' => 'اسٹور کورئیر',
            'description_en' => 'Doorstep delivery in city limits.',
            'description_ur' => 'شہر کی حد میں ڈیلیوری۔',
            'sort_order' => 1,
            'status' => DeliveryMethod::STATUS_ACTIVE,
            'allowed_payment_methods' => SaleService::PAYMENT_METHODS,
            'cod_allowed' => true,
            'cod_min_advance_percent' => 0,
            'full_advance_required' => false,
            'customer_notice_en' => 'Pack carefully for fertilizer.',
            'customer_notice_ur' => 'کھاد احتیاط سے پیک کریں۔',
            'shipping_charge_mode' => ShippingChargeMode::CalculateLater->value,
        ]);

        $product->deliveryMethods()->sync([$method->id]);

        $response = $this->get(route('store.product', $product->slug));

        $response
            ->assertOk()
            ->assertSee('data-store-pdp-tab="delivery"', false)
            ->assertSee('aria-controls="store-pdp-panel-delivery"', false)
            ->assertSee('Delivery', false)
            ->assertSee('Store Courier', false)
            ->assertSee('Doorstep delivery in city limits.', false)
            ->assertSee('Pack carefully for fertilizer.', false)
            ->assertSee(__('agricart.storefront.checkout.shipping_modes.calculate_later'), false);
    }

    public function test_product_controls_render_as_linear_content_tabs_not_side_specs(): void
    {
        $category = Category::query()->create([
            'slug' => 'parts',
            'code' => 'CAT-PARTS',
            'status' => 'active',
            'name_en' => 'Parts',
            'name_ur' => 'پارٹس',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-CTL-1',
            'sku' => 'CTL-1',
            'name_en' => 'Bearing Kit',
            'name_ur' => 'بیرنگ کٹ',
            'slug' => 'bearing-kit',
            'full_description_en' => 'A durable bearing kit.',
        ]);

        $control = CatalogControl::query()->create([
            'code' => 'CTL-WARRANTY',
            'slug' => 'warranty-policy',
            'status' => 'active',
            'name_en' => 'Warranty policy',
            'name_ur' => 'وارنٹی پالیسی',
            'value_en' => str_repeat('Long storefront control text. ', 12),
            'value_ur' => 'تفصیلی وارنٹی معلومات۔',
        ]);

        $product->controls()->attach($control->id);

        $response = $this->get(route('store.product', $product->slug));

        $response
            ->assertOk()
            ->assertSeeInOrder(['Description', 'Warranty policy'])
            ->assertSee('Long storefront control text.', false)
            ->assertSee('data-store-pdp-tab="content-1"', false)
            ->assertDontSee('ag-store-pdp__spec-controls', false)
            ->assertDontSee('ag-store-pdp__spec-notice', false);
    }

    public function test_product_page_hides_delivery_tab_when_no_methods_assigned(): void
    {
        $category = Category::query()->create([
            'slug' => 'tools',
            'code' => 'CAT-TOOLS',
            'status' => 'active',
            'name_en' => 'Tools',
            'name_ur' => 'اوزار',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-NO-DEL',
            'sku' => 'SPADE-1',
            'name_en' => 'Spade',
            'name_ur' => 'بیلچہ',
            'slug' => 'spade',
            'full_description_en' => 'Garden spade.',
        ]);

        $response = $this->get(route('store.product', $product->slug));

        $response
            ->assertOk()
            ->assertDontSee('data-store-pdp-tab="delivery"', false);
    }

    public function test_hidden_prices_still_show_quantity_pricing_tab(): void
    {
        $category = Category::query()->create([
            'slug' => 'filters',
            'code' => 'CAT-FILTERS',
            'status' => 'active',
            'name_en' => 'Filters',
            'name_ur' => 'فلٹرز',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-QTY-1',
            'sku' => 'MESH-1',
            'name_en' => 'Boom Arm Filter Mesh',
            'name_ur' => 'بوم آرم فلٹر میش',
            'slug' => 'boom-arm-filter-mesh',
            'retail_price' => 250,
            'stock_qty' => 20,
        ]);

        // Disabled channel (or maintenance) sets show_prices=false; full maintenance returns 503.
        $channel = SalesChannel::onlineStore();
        $channel->update(['is_enabled' => false, 'maintenance_mode' => false]);
        app(SalesChannelService::class)->clearCache();

        $this->assertFalse(app(SalesChannelService::class)->onlineStoreShowsPrices());

        $response = $this->get(route('store.product', $product->slug));

        $response
            ->assertOk()
            ->assertSee('data-store-pdp-tab="pricing"', false)
            ->assertSee(__('agricart.storefront.product.tabs.pricing'), false)
            ->assertSee('ag-store-pdp__price-table', false)
            ->assertDontSee('ag-store-pdp__price-box', false)
            ->assertSee('ag-store-pdp__offline-msg', false)
            ->assertDontSee(__('agricart.storefront.product.add_to_cart'), false);
    }
}
