<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use App\Services\Store\StoreCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseSixStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_search_is_an_accessible_progressively_enhanced_combobox(): void
    {
        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee('role="combobox"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('aria-controls="ag-store-search-suggest"', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee('action="'.route('store.search').'"', false);
    }

    public function test_search_pages_are_noindex_and_exact_codes_rank_first(): void
    {
        $category = $this->category();
        $exact = $this->product($category, 'exact-code', 'Unrelated name', 'ABC-100');
        $this->product($category, 'contains-code', 'ABC-100 compatible product', 'XYZ-200');

        $response = $this->getJson(route('store.search.suggest', ['q' => 'ABC-100']))
            ->assertOk();

        $products = collect($response->json('results'))->where('type', 'product')->values();
        $this->assertSame($exact->id, $products->first()['id']);

        $this->get(route('store.search', ['q' => 'ABC-100']))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,follow">', false);
    }

    public function test_category_has_localized_h1_canonical_social_and_structured_metadata(): void
    {
        $category = $this->category([
            'name_en' => 'Crop Care',
            'seo_title_en' => 'Crop Care Supplies',
            'seo_description_en' => 'Trusted crop care supplies.',
        ]);

        $this->get(route('store.category', $category->slug))
            ->assertOk()
            ->assertSee('ag-store-page-title', false)
            ->assertSee('Crop Care', false)
            ->assertSee('<link rel="canonical" href="'.route('store.category', $category->slug).'">', false)
            ->assertSee('<meta property="og:title" content="Crop Care Supplies', false)
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_product_schema_uses_actual_stock_and_locale_redirects_are_not_hreflang(): void
    {
        $product = $this->product($this->category(), 'out-of-stock', 'Out of stock', 'OOS-1');
        $product->update(['stock_qty' => 0, 'retail_price' => 100]);

        $this->get(route('store.product', $product->slug))
            ->assertOk()
            ->assertSee('https://schema.org/OutOfStock', false)
            ->assertSee('"price":"100.00"', false)
            ->assertDontSee('hreflang=', false);
    }

    public function test_home_includes_organization_and_search_action_schema(): void
    {
        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee('"@type":"LocalBusiness"', false)
            ->assertSee('"@type":"WebSite"', false)
            ->assertSee('"@type":"SearchAction"', false)
            ->assertSee('"@type":"EntryPoint"', false)
            ->assertSee('urlTemplate', false);
    }

    public function test_mobile_cart_quantity_has_contextual_accessible_name_and_labels(): void
    {
        $product = $this->product($this->category(), 'cart-accessible', 'Accessible Product', 'ACC-1');
        $product->update(['stock_qty' => 5, 'retail_price' => 100]);
        app(StoreCartService::class)->add($product->id, 1);

        $this->get(route('store.cart'))
            ->assertOk()
            ->assertSee('aria-label="'.__('agricart.storefront.cart.col_quantity').': Accessible Product"', false)
            ->assertSee('data-label="'.__('agricart.storefront.cart.col_total').'"', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function category(array $overrides = []): Category
    {
        return Category::query()->create(array_merge([
            'slug' => 'crop-care',
            'code' => 'CAT-CROP',
            'status' => 'active',
            'name_en' => 'Crop Care',
            'name_ur' => 'فصل کی دیکھ بھال',
        ], $overrides));
    }

    private function product(Category $category, string $slug, string $name, string $code): Product
    {
        return Product::query()->create([
            'category_id' => $category->id,
            'slug' => $slug,
            'code' => $code,
            'sku' => $code,
            'status' => 'active',
            'name_en' => $name,
            'name_ur' => $name,
        ]);
    }
}
